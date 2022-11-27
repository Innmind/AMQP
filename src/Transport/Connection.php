<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Connection\Start,
    Transport\Connection\Handshake,
    Transport\Connection\OpenVHost,
    Transport\Connection\Heartbeat,
    Transport\Connection\FrameReader,
    Transport\Connection\State,
    Transport\Connection\Continuation,
    Transport\Frame,
    Transport\Protocol,
    Transport\Frame\Type,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Model\Connection\StartOk,
    Model\Connection\SecureOk,
    Model\Connection\TuneOk,
    Model\Connection\Open,
    Model\Connection\Close,
    Model\Connection\MaxChannels,
    Model\Connection\MaxFrameSize,
    Failure,
};
use Innmind\Socket\{
    Internet\Transport,
    Client as Socket,
};
use Innmind\Stream\Watch;
use Innmind\Url\Url;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    Clock,
    PointInTime,
    Earth,
};
use Innmind\OperatingSystem\{
    Remote,
    Sockets,
};
use Innmind\Immutable\{
    Str,
    Set,
    Maybe,
    Either,
    Sequence,
    SideEffect,
    Predicate\Instance,
};

final class Connection
{
    private Protocol $protocol;
    private Sockets $sockets;
    private Socket $socket;
    private Watch $watch;
    private FrameReader $read;
    private State $state;
    private MaxChannels $maxChannels;
    private MaxFrameSize $maxFrameSize;
    private Heartbeat $heartbeat;

    private function __construct(
        Protocol $protocol,
        Sockets $sockets,
        Heartbeat $heartbeat,
        Socket $socket,
        Watch $watch,
        State $state,
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        FrameReader $read,
    ) {
        $this->state = $state;
        $this->protocol = $protocol;
        $this->sockets = $sockets;
        $this->socket = $socket;
        $this->watch = $watch;
        $this->read = $read;
        $this->maxChannels = $maxChannels;
        $this->maxFrameSize = $maxFrameSize;
        $this->heartbeat = $heartbeat;
    }

    /**
     * @return Maybe<self>
     */
    public static function open(
        Transport $transport,
        Url $server,
        Protocol $protocol,
        ElapsedPeriod $timeout,
        Clock $clock,
        Remote $remote,
        Sockets $sockets,
    ): Maybe {
        /**
         * Due to the $socket->write() psalm lose the type
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidArgument
         */
        return $remote
            ->socket(
                $transport,
                $server->authority()->withoutUserInformation(),
            )
            ->flatMap(
                static fn($socket) => $socket
                    ->write($protocol->version()->pack())
                    ->maybe(),
            )
            ->map(static fn($socket) => new self(
                $protocol,
                $sockets,
                Heartbeat::start($clock, $timeout),
                $socket,
                $sockets->watch($timeout)->forRead($socket),
                State::opening,
                MaxChannels::unlimited(),
                MaxFrameSize::unlimited(),
                new FrameReader,
            ))
            ->flatMap(new Start($server->authority()))
            ->flatMap(new Handshake($server->authority()))
            ->flatMap(new OpenVHost($server->path()))
            ->map(static fn($connection) => $connection->ready());
    }

    /**
     * When it contains the same connection instance it means that you can still
     * use the connection, otherwise you should stop using it
     *
     * Possible failures are exceeding the max channel number, the max frame size
     * or that writting to the socket failed
     *
     * @param callable(Protocol, MaxFrameSize): Sequence<Frame> $frames
     */
    public function send(callable $frames): Continuation
    {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var Either<Failure, self>
         */
        $connection = $frames($this->protocol, $this->maxFrameSize)->reduce(
            Either::right($this),
            static fn(Either $connection, $frame) => $connection->flatMap(
                static fn(self $connection) => $connection->sendFrame($frame),
            ),
        );

        return Continuation::of($connection);
    }

    /**
     * @return Either<Failure, Received>
     */
    public function wait(Frame\Method ...$names): Either
    {
        do {
            if (!$this->state->listenable($this->socket)) {
                /** @var Either<Failure, Received> */
                return Either::left(Failure::toReadFrame);
            }

            $this->heartbeat->ping($this);

            /** @var Set<Socket> */
            $toRead = ($this->watch)()->match(
                static fn($ready) => $ready->toRead(),
                static fn() => Set::of(),
            );
        } while (!$toRead->contains($this->socket));

        return ($this->read)($this->socket, $this->protocol)
            ->map(fn($frame) => Received::of(
                $this->asActive(),
                $frame,
            ))
            ->either()
            ->leftMap(static fn() => Failure::toReadFrame)
            ->flatMap(fn($received) => match ($received->frame()->type()) {
                Type::heartbeat => $this->wait(...$names),
                default => $this->ensureValidFrame($received, ...$names),
            });
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        if (!$this->state->usable($this->socket)) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        try {
            return $this
                ->send(static fn($protocol) => $protocol->connection()->close(Close::demand()))
                ->wait(Method::connectionCloseOk)
                ->either()
                ->flatMap(static fn($connection) => $connection->socket->close())
                ->maybe()
                ->map(static fn() => new SideEffect);
        } finally {
            // we modify the state of the current instance instead of creating a
            // new instance like in self::ready() to prevent anyone from trying
            // to reuse this instance after it has been closed
            $this->state = State::closed;
        }
    }

    /**
     * This only modify the internal values for the connection, it doesn't
     * notify the server we applied the changes on our end. The notification is
     * done in Handshake
     *
     * @internal
     */
    public function tune(
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        ElapsedPeriod $heartbeat,
    ): self {
        return new self(
            $this->protocol,
            $this->sockets,
            $this->heartbeat->adjust($heartbeat),
            $this->socket,
            $this->sockets->watch($heartbeat)->forRead($this->socket),
            $this->state,
            $maxChannels,
            $maxFrameSize,
            $this->read,
        );
    }

    /**
     * @internal
     */
    public function ready(): self
    {
        return new self(
            $this->protocol,
            $this->sockets,
            $this->heartbeat,
            $this->socket,
            $this->watch,
            State::opened,
            $this->maxChannels,
            $this->maxFrameSize,
            $this->read,
        );
    }

    public function asActive(): self
    {
        return new self(
            $this->protocol,
            $this->sockets,
            $this->heartbeat->active(),
            $this->socket,
            $this->watch,
            $this->state,
            $this->maxChannels,
            $this->maxFrameSize,
            $this->read,
        );
    }

    /**
     * @return Either<Failure, self>
     */
    private function sendFrame(Frame $frame): Either
    {
        /** @var Either<Failure, self> */
        return Maybe::just($frame)
            ->filter(fn($frame) => $this->maxChannels->allows($frame->channel()->toInt()))
            ->map(static fn($frame) => $frame->pack()->toEncoding('ASCII'))
            ->filter(fn($frame) => $this->maxFrameSize->allows($frame->length()))
            ->flatMap(
                fn($frame) => $this
                    ->socket
                    ->write($frame)
                    ->maybe(),
            )
            ->map(fn() => $this)
            ->either()
            ->leftMap(static fn() => Failure::toSendFrame);
    }

    /**
     * @return Either<Failure, Received>
     */
    private function ensureValidFrame(
        Received $received,
        Method ...$names,
    ): Either {
        if (\count($names) === 0) {
            /** @var Either<Failure, Received> */
            return Either::right($received);
        }

        if ($received->frame()->type() !== Type::method) {
            // someone must have forgot a wait() call
            /** @var Either<Failure, Received> */
            return Either::left(Failure::unexpectedFrame);
        }

        if ($received->oneOf(...$names)) {
            /** @var Either<Failure, Received> */
            return Either::right($received);
        }

        if ($received->frame()->is(Method::connectionClose)) {
            /** @var Either<Failure, Received> */
            return $received
                ->connection()
                ->send(static fn($protocol) => $protocol->connection()->closeOk())
                ->either()
                ->leftMap(static fn() => Failure::toCloseConnection)
                ->map(static function($connection) {
                    $connection->state = State::closed;

                    return $connection;
                })
                ->flatMap(static function() use ($received) {
                    $message = $received
                        ->frame()
                        ->values()
                        ->get(1)
                        ->keep(Instance::of(Value\ShortString::class))
                        ->map(static fn($value) => $value->original()->toString());
                    $code = $received
                        ->frame()
                        ->values()
                        ->first()
                        ->keep(Instance::of(Value\UnsignedShortInteger::class))
                        ->map(static fn($value) => $value->original());
                    $class = $received
                        ->frame()
                        ->values()
                        ->get(2)
                        ->keep(Instance::of(Value\UnsignedShortInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->filter(static fn($class) => $class !== 0);
                    $method = $received
                        ->frame()
                        ->values()
                        ->get(3)
                        ->keep(Instance::of(Value\UnsignedShortInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->filter(static fn($method) => $method !== 0);
                    $method = Maybe::all($class, $method)->map(
                        static fn(int $class, int $method) => Method::of($class, $method),
                    );

                    // TODO give access to the information above to the user
                    return Either::left(Failure::closedByServer);
                });
        }

        /** @var Either<Failure, Received> */
        return Either::left(Failure::unexpectedFrame);
    }
}
