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
    Transport\Frame\Value\UnsignedOctet,
    Model\Connection\StartOk,
    Model\Connection\SecureOk,
    Model\Connection\TuneOk,
    Model\Connection\Open,
    Model\Connection\Close,
    Model\Connection\MaxChannels,
    Model\Connection\MaxFrameSize,
    Exception\UnexpectedFrame,
    Exception\NoFrameDetected,
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
    private Transport $transport;
    private Authority $authority;
    private Path $vhost;
    private Protocol $protocol;
    private Socket $socket;
    private ElapsedPeriod $timeout;
    private Remote $remote;
    private Sockets $sockets;
    private Watch $watch;
    private FrameReader $read;
    private bool $closed = true;
    private bool $opening = true;
    private MaxChannels $maxChannels;
    private MaxFrameSize $maxFrameSize;
    private ElapsedPeriod $heartbeat;
    private Clock $clock;
    private PointInTime $lastReceivedData;

    public function __construct(
        Transport $transport,
        Url $server,
        Protocol $protocol,
        ElapsedPeriod $timeout,
        Clock $clock,
        Remote $remote,
        Sockets $sockets,
    ) {
        $this->transport = $transport;
        $this->authority = $server->authority();
        $this->vhost = $server->path();
        $this->protocol = $protocol;
        $this->timeout = $timeout;
        $this->remote = $remote;
        $this->sockets = $sockets;
        $this->buildSocket();
        $this->read = new FrameReader;
        $this->maxChannels = new MaxChannels(0);
        $this->maxFrameSize = new MaxFrameSize(0);
        $this->heartbeat = $timeout;
        $this->clock = $clock;
        $this->lastReceivedData = $clock->now();

        $this->open();
    }

    public function protocol(): Protocol
    {
        return $this->protocol;
    }

    public function send(Frame $frame): void
    {
        $this->maxChannels->verify($frame->channel()->toInt());

        $frame = Str::of($frame->toString())->toEncoding('ASCII');

        $this->maxFrameSize->verify($frame->length());
        $this->socket->write($frame);
    }

    public function wait(Frame\Method ...$names): Frame
    {
        do {
            if (!$this->opening && $this->closed()) {
                throw new ConnectionClosed;
            }

            $now = $this->clock->now();
            $elapsedPeriod = $now->elapsedSince($this->lastReceivedData);

            if ($elapsedPeriod->longerThan($this->heartbeat)) {
                $this->send(Frame::heartbeat());
            }

            /** @var Set<Socket> */
            $toRead = ($this->watch)()->match(
                static fn($ready) => $ready->toRead(),
                static fn() => Set::of(),
            );
        } while (!$toRead->contains($this->socket));

        $frame = ($this->read)($this->socket, $this->protocol);
        $this->lastReceivedData = $this->clock->now();

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
            $this->closed = true;

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
                ->map(static fn($value) => $value->original()->value())
                ->filter(static fn($class) => $class !== 0);
            $method = $frame
                ->values()
                ->get(3)
                ->keep(Instance::of(Value\UnsignedShortInteger::class))
                ->map(static fn($value) => $value->original()->value())
                ->filter(static fn($method) => $method !== 0);

            throw ConnectionClosed::byServer(
                $message->original()->toString(),
                $code->original()->value(),
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
        if ($this->closed()) {
            return;
        }

        $this->send($this->protocol->connection()->close(new Close));
        $this->wait(Method::connectionCloseOk);
        $this->socket->close();
        $this->closed = true;
    }

    public function closed(): bool
    {
        return $this->closed || $this->socket->closed();
    }

    private function buildSocket(): void
    {
        $this->socket = $this
            ->remote
            ->socket(
                $this->transport,
                $this->authority->withoutUserInformation(),
            )
            ->match(
                static fn($socket) => $socket,
                static fn() => throw new \RuntimeException,
            );
        /** @psalm-suppress InvalidArgument */
        $this->watch = $this->sockets->watch($this->timeout)->forRead($this->socket);
    }

    private function open(): void
    {
        if (!$this->closed()) {
            return;
        }

        $this->start();
        $this->handshake();
        $this->openVHost();

        $this->closed = false;
        $this->opening = false;
    }

    private function start(): void
    {
        $this->socket->write(
            $this->protocol->version()->pack(),
        );

        try {
            $this->wait(Method::connectionStart);
        } catch (NoFrameDetected $e) {
            $content = $e->content();
            $protocol = $content->read(4)->match(
                static fn($protocol) => $protocol,
                static fn() => throw new \LogicException,
            );

            if ($protocol->toString() !== 'AMQP') {
                throw $e;
            }

            $_ = $content->read(1)->match(
                static fn() => null,
                static fn() => throw new \LogicException,
            ); // there is a zero between AMQP and version number

            $this->protocol->use(
                UnsignedOctet::unpack($content)->original()->value(),
                UnsignedOctet::unpack($content)->original()->value(),
                UnsignedOctet::unpack($content)->original()->value(),
            );
            // socket rebuilt as the server close the connection on version mismatch
            $this->buildSocket();
            $this->start();

            return;
        }

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
            $maxChannels->original()->value(),
        );
        /** @var Value\UnsignedLongInteger */
        $maxFrameSize = $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $this->maxFrameSize = new MaxFrameSize(
            $maxFrameSize->original()->value(),
        );
        /** @var Value\UnsignedShortInteger */
        $heartbeat = $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $this->heartbeat = new Earth\ElapsedPeriod(
            $heartbeat->original()->value(),
        );
        $this->watch = $this->sockets->watch($this->heartbeat)->forRead($this->socket);
        $this->send($this->protocol->connection()->tuneOk(
            new TuneOk(
                $this->maxChannels,
                $this->maxFrameSize,
                $this->heartbeat,
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
