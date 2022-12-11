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
    Transport\Connection\SignalListener,
    Transport\Frame,
    Transport\Protocol,
    Transport\Frame\Channel,
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
use Innmind\OperatingSystem\CurrentProcess\Signals;
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

/**
 * @internal You should use the Client instead
 */
final class Connection
{
    private Protocol $protocol;
    private Sockets $sockets;
    private Socket $socket;
    private Watch $watch;
    private FrameReader $read;
    private MaxChannels $maxChannels;
    private MaxFrameSize $maxFrameSize;
    private Heartbeat $heartbeat;
    private SignalListener $signals;

    private function __construct(
        Protocol $protocol,
        Sockets $sockets,
        Heartbeat $heartbeat,
        Socket $socket,
        Watch $watch,
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        FrameReader $read,
        SignalListener $signals,
    ) {
        $this->protocol = $protocol;
        $this->sockets = $sockets;
        $this->socket = $socket;
        $this->watch = $watch;
        $this->read = $read;
        $this->maxChannels = $maxChannels;
        $this->maxFrameSize = $maxFrameSize;
        $this->heartbeat = $heartbeat;
        $this->signals = $signals;
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
                MaxChannels::unlimited(),
                MaxFrameSize::unlimited(),
                new FrameReader,
                SignalListener::uninstalled(),
            ))
            ->flatMap(new Start($server->authority()))
            ->flatMap(new Handshake($server->authority()))
            ->flatMap(new OpenVHost($server->path()));
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
            $this->signals->safe($this),
            static fn(Either $connection, $frame) => $connection->flatMap(
                static fn(self $connection) => $connection->sendFrame($frame),
            ),
        );

        return Continuation::of($connection);
    }

    /**
     * @return Either<Failure, ReceivedFrame>
     */
    public function wait(Frame\Method ...$names): Either
    {
        /** @var Either<Failure, array{Connection, Set<Socket>}> */
        $ready = Either::right([$this, Set::of()]);

        do {
            $ready = $ready->flatMap($this->doWait(...));
        } while ($ready->match(
            static fn($ready) => !$ready[1]->contains($ready[0]->socket),
            static fn() => false,
        ));

        return $ready
            ->maybe()
            ->map(static fn($ready) => $ready[0])
            ->flatMap(
                static fn($connection) => ($connection->read)(
                    $connection->socket,
                    $connection->protocol,
                )
                    ->map(static fn($frame) => ReceivedFrame::of(
                        $connection->asActive(),
                        $frame,
                    )),
            )
            ->either()
            ->leftMap(static fn() => Failure::toReadFrame())
            ->flatMap(static fn($received) => match ($received->frame()->type()) {
                Type::heartbeat => $received->connection()->wait(...$names),
                default => $received->connection()->ensureValidFrame($received, ...$names),
            });
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        return $this
            ->send(static fn($protocol) => $protocol->connection()->close(Close::demand()))
            ->wait(Method::connectionCloseOk)
            ->connection()
            ->flatMap(static fn($connection) => $connection->socket->close())
            ->maybe()
            ->map(static fn() => new SideEffect);
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
            $maxChannels,
            $maxFrameSize,
            $this->read,
            $this->signals,
        );
    }

    /**
     * @internal
     */
    public function asActive(): self
    {
        return new self(
            $this->protocol,
            $this->sockets,
            $this->heartbeat->active(),
            $this->socket,
            $this->watch,
            $this->maxChannels,
            $this->maxFrameSize,
            $this->read,
            $this->signals,
        );
    }

    public function listenSignals(Signals $signals, Channel $channel): self
    {
        return new self(
            $this->protocol,
            $this->sockets,
            $this->heartbeat,
            $this->socket,
            $this->watch,
            $this->maxChannels,
            $this->maxFrameSize,
            $this->read,
            $this->signals->install($signals, $channel),
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
            ->leftMap(static fn() => Failure::toSendFrame());
    }

    /**
     * @param array{Connection, Set<Socket>} $in
     *
     * @return Either<Failure, array{Connection, Set<Socket>}>
     */
    private function doWait(array $in): Either
    {
        [$connection] = $in;

        if ($connection->closed()) {
            /** @var Either<Failure, array{Connection, Set<Socket>}> */
            return Either::left(Failure::toReadFrame());
        }

        /** @var Either<Failure, array{Connection, Set<Socket>}> */
        return $connection
            ->signals
            ->safe($connection)
            ->flatMap(static fn($connection) => $connection->heartbeat->ping($connection))
            ->map(static fn($connection) => [
                $connection,
                ($connection->watch)()->match(
                    static fn($ready) => $ready->toRead(),
                    static fn() => Set::of(),
                ),
            ]);
    }

    /**
     * @return Either<Failure, ReceivedFrame>
     */
    private function ensureValidFrame(
        ReceivedFrame $received,
        Method ...$names,
    ): Either {
        if (\count($names) === 0) {
            /** @var Either<Failure, ReceivedFrame> */
            return Either::right($received);
        }

        if ($received->frame()->type() !== Type::method) {
            // someone must have forgot a wait() call
            /** @var Either<Failure, ReceivedFrame> */
            return Either::left(Failure::unexpectedFrame());
        }

        if ($received->oneOf(...$names)) {
            /** @var Either<Failure, ReceivedFrame> */
            return Either::right($received);
        }

        if ($received->frame()->is(Method::connectionClose)) {
            /** @var Either<Failure, ReceivedFrame> */
            return $received
                ->connection()
                ->send(static fn($protocol) => $protocol->connection()->closeOk())
                ->connection()
                ->leftMap(static fn() => Failure::toCloseConnection())
                ->flatMap(static function() use ($received) {
                    $message = $received
                        ->frame()
                        ->values()
                        ->get(1)
                        ->keep(Instance::of(Value\ShortString::class))
                        ->map(static fn($value) => $value->original()->toString())
                        ->match(
                            static fn($message) => $message,
                            static fn() => 'Invalid message sent by server',
                        );
                    $code = $received
                        ->frame()
                        ->values()
                        ->first()
                        ->keep(Instance::of(Value\UnsignedShortInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->match(
                            static fn($code) => $code,
                            static fn() => 0,
                        );
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

                    return Either::left(Failure::closedByServer($message, $code, $method));
                });
        }

        /** @var Either<Failure, ReceivedFrame> */
        return Either::left(Failure::unexpectedFrame());
    }

    private function closed(): bool
    {
        return $this->socket->closed();
    }
}
