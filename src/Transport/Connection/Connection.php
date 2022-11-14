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
use Innmind\Url\{
    Url,
    Path,
    Authority,
};
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
    private Authority $authority;
    private Path $vhost;
    private Protocol $protocol;
    private Socket $socket;
    private Sockets $sockets;
    private Watch $watch;
    private FrameReader $read;
    private State $state;
    private MaxChannels $maxChannels;
    private MaxFrameSize $maxFrameSize;
    private Heartbeat $heartbeat;

    private function __construct(
        Url $server,
        Protocol $protocol,
        ElapsedPeriod $timeout,
        Clock $clock,
        Sockets $sockets,
        Socket $socket,
        Watch $watch,
    ) {
        $this->state = State::opening;
        $this->authority = $server->authority();
        $this->vhost = $server->path();
        $this->protocol = $protocol;
        $this->sockets = $sockets;
        $this->socket = $socket;
        $this->watch = $watch;
        $this->read = new FrameReader;
        $this->maxChannels = new MaxChannels(0);
        $this->maxFrameSize = new MaxFrameSize(0);
        $this->heartbeat = new Heartbeat($clock, $timeout);

        $this->open();
    }

    public static function of(
        Transport $transport,
        Url $server,
        Protocol $protocol,
        ElapsedPeriod $timeout,
        Clock $clock,
        Remote $remote,
        Sockets $sockets,
    ): self {
        $socket = $remote
            ->socket(
                $transport,
                $server->authority()->withoutUserInformation(),
            )
            ->match(
                static fn($socket) => $socket,
                static fn() => throw new \RuntimeException,
            );

        return new self(
            $server,
            $protocol,
            $timeout,
            $clock,
            $sockets,
            $socket,
            $sockets->watch($timeout)->forRead($socket),
        );
    }

    public function protocol(): Protocol
    {
        return $this->protocol;
    }

    public function send(Frame $frame): void
    {
        $this->maxChannels->verify($frame->channel()->toInt());

        $frame = $frame->pack()->toEncoding('ASCII');

        $this->maxFrameSize->verify($frame->length());
        $_ = $this->socket->write($frame)->match(
            static fn() => null,
            static fn() => throw new \RuntimeException,
        );
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

        $this->send($this->protocol->connection()->close(new Close));
        $this->wait(Method::connectionCloseOk);
        $this->socket->close();
        $this->state = State::closed;
    }

    public function closed(): bool
    {
        return $this->state->closed($this->socket);
    }

    private function open(): void
    {
        if (!$this->state->openable($this->socket)) {
            return;
        }

        $this->start();
        $this->handshake();
        $this->openVHost();

        $this->state = State::opened;
    }

    private function start(): void
    {
        $_ = $this
            ->socket
            ->write($this->protocol->version()->pack())
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );

        // at this point the server could respond with a simple text "AMQP0xyz"
        // where xyz represent the version of the protocol it supports meaning
        // we should restart the opening sequence with this version of the
        // protocol but since this package only support 0.9.1 we can simply
        // stop opening the connection
        $this->wait(Method::connectionStart);
        $this->send($this->protocol->connection()->startOk(
            new StartOk(
                $this->authority->userInformation()->user(),
                $this->authority->userInformation()->password(),
            ),
        ));
    }

    private function handshake(): void
    {
        $frame = $this->wait(Method::connectionSecure, Method::connectionTune);

        if ($frame->is(Method::connectionSecure)) {
            $this->send($this->protocol->connection()->secureOk(
                new SecureOk(
                    $this->authority->userInformation()->user(),
                    $this->authority->userInformation()->password(),
                ),
            ));
            $frame = $this->wait(Method::connectionTune);
        }

        /** @var Value\UnsignedShortInteger */
        $maxChannels = $frame->values()->get(0)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $this->maxChannels = new MaxChannels(
            $maxChannels->original(),
        );
        /** @var Value\UnsignedLongInteger */
        $maxFrameSize = $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $this->maxFrameSize = new MaxFrameSize($maxFrameSize->original());
        /** @var Value\UnsignedShortInteger */
        $heartbeat = $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $threshold = new Earth\ElapsedPeriod($heartbeat->original());
        $this->heartbeat->adjust($threshold);
        $this->watch = $this->sockets->watch($threshold)->forRead($this->socket);
        $this->send($this->protocol->connection()->tuneOk(
            new TuneOk(
                $this->maxChannels,
                $this->maxFrameSize,
                $threshold,
            ),
        ));
    }

    private function openVHost(): void
    {
        $this->send($this->protocol->connection()->open(
            new Open($this->vhost),
        ));
        $this->wait(Method::connectionOpenOk);
    }
}
