<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection as ConnectionInterface,
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
    Exception\UnexpectedFrame,
    Exception\ConnectionClosed,
    Exception\ExpectedMethodFrame,
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
    Predicate\Instance,
};

final class Connection implements ConnectionInterface
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
    ) {
        $this->state = $state;
        $this->protocol = $protocol;
        $this->sockets = $sockets;
        $this->socket = $socket;
        $this->watch = $watch;
        $this->read = new FrameReader;
        $this->maxChannels = $maxChannels;
        $this->maxFrameSize = $maxFrameSize;
        $this->heartbeat = $heartbeat;
    }

    /**
     * @return Maybe<self>
     */
    public static function of(
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
                new Heartbeat($clock, $timeout),
                $socket,
                $sockets->watch($timeout)->forRead($socket),
                State::opening,
                MaxChannels::unlimited(),
                MaxFrameSize::unlimited(),
            ))
            ->map(new Start($server->authority()))
            ->map(new Handshake($server->authority()))
            ->map(new OpenVHost($server->path()))
            ->map(static fn($connection) => $connection->ready());
    }

    public function protocol(): Protocol
    {
        return $this->protocol;
    }

    public function send(Frame $frame): Maybe
    {
        /** @var Maybe<ConnectionInterface> */
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
            ->map(fn() => $this);
    }

    public function wait(Frame\Method ...$names): Frame
    {
        do {
            if (!$this->state->listenable($this->socket)) {
                throw new ConnectionClosed;
            }

            $this->heartbeat->ping($this);

            /** @var Set<Socket> */
            $toRead = ($this->watch)()->match(
                static fn($ready) => $ready->toRead(),
                static fn() => Set::of(),
            );
        } while (!$toRead->contains($this->socket));

        $frame = ($this->read)($this->socket, $this->protocol)->match(
            static fn($frame) => $frame,
            static fn() => throw new \RuntimeException,
        );
        $this->heartbeat->active();

        if ($frame->type() === Type::heartbeat) {
            return $this->wait(...$names);
        }

        if (\count($names) === 0) {
            return $frame;
        }

        if ($frame->type() !== Type::method) {
            // someone must have forgot a wait() call
            throw new ExpectedMethodFrame($frame->type());
        }

        foreach ($names as $name) {
            if ($frame->is($name)) {
                return $frame;
            }
        }

        if ($frame->is(Method::connectionClose)) {
            $this->send($this->protocol->connection()->closeOk());
            $this->state = State::closed;

            /** @var Value\ShortString */
            $message = $frame->values()->get(1)->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            /** @var Value\UnsignedShortInteger */
            $code = $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            $class = $frame
                ->values()
                ->get(2)
                ->keep(Instance::of(Value\UnsignedShortInteger::class))
                ->map(static fn($value) => $value->original())
                ->filter(static fn($class) => $class !== 0);
            $method = $frame
                ->values()
                ->get(3)
                ->keep(Instance::of(Value\UnsignedShortInteger::class))
                ->map(static fn($value) => $value->original())
                ->filter(static fn($method) => $method !== 0);

            throw ConnectionClosed::byServer(
                $message->original()->toString(),
                $code->original(),
                Maybe::all($class, $method)->map(
                    static fn(int $class, int $method) => Method::of($class, $method),
                ),
            );
        }

        throw new UnexpectedFrame($frame, ...$names);
    }

    public function maxFrameSize(): MaxFrameSize
    {
        return $this->maxFrameSize;
    }

    public function close(): void
    {
        if (!$this->state->usable($this->socket)) {
            return;
        }

        $this->send($this->protocol->connection()->close(Close::demand()));
        $this->wait(Method::connectionCloseOk);
        $this->socket->close();
        // we modify the state of the current instance instead of creating a new
        // instance like in self::ready() to prevent anyone from trying to reuse
        // this instance after it has been closed
        $this->state = State::closed;
    }

    public function closed(): bool
    {
        return $this->state->closed($this->socket);
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
        );
    }
}
