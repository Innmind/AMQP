<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Connection\FrameReader,
    Transport\Protocol\Version,
    Transport\Frame\Value\UnsignedOctet,
    Model\Connection\StartOk,
    Model\Connection\SecureOk,
    Model\Connection\TuneOk,
    Model\Connection\Open,
    Model\Connection\Close,
    Model\Connection\MaxChannels,
    Model\Connection\MaxFrameSize,
    Exception\FrameChannelExceedAllowedChannelNumber,
    Exception\FrameExceedAllowedSize,
    Exception\UnexpectedFrame,
    Exception\NoFrameDetected
};
use Innmind\Socket\{
    Internet\Transport,
    Client\Internet as Socket
};
use Innmind\Stream\Select;
use Innmind\Url\{
    UrlInterface,
    Authority\NullUserInformation
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Immutable\Str;

final class Connection
{
    private $transport;
    private $authority;
    private $vhost;
    private $protocol;
    private $socket;
    private $timeout;
    private $select;
    private $read;
    private $opened = false;
    private $maxChannels;
    private $maxFrameSize;
    private $heartbeat;

    public function __construct(
        Transport $transport,
        UrlInterface $server,
        Protocol $protocol,
        ElapsedPeriod $timeout
    ) {
        $this->transport = $transport;
        $this->authority = $server->authority();
        $this->vhost = $server->path();
        $this->protocol = $protocol;
        $this->timeout = $timeout;
        $this->buildSocket();
        $this->read = new FrameReader;
        $this->maxChannels = new MaxChannels(0);
        $this->maxFrameSize = new MaxFrameSize(0);
        $this->heartbeat = $timeout;

        $this->open();
    }

    public function protocol(): Protocol
    {
        return $this->protocol;
    }

    public function send(Frame $frame): self
    {
        if (!$this->maxChannels->allows($frame->channel()->toInt())) {
            throw new FrameChannelExceedAllowedChannelNumber(
                $frame->channel(),
                $this->maxChannels
            );
        }

        $frame = (new Str((string) $frame))->toEncoding('ASCII');

        if (!$this->maxFrameSize->allows($frame->length())) {
            throw new FrameExceedAllowedSize(
                $frame->length(),
                $this->maxFrameSize
            );
        }

        $this->socket->write($frame);

        return $this;
    }

    public function wait(string ...$names): Frame
    {
        do {
            $streams = ($this->select)();
        } while (!$streams->get('read')->contains($this->socket));

        $frame = ($this->read)($this->socket, $this->protocol);

        foreach ($names as $name) {
            if ($this->protocol->method($name)->equals($frame->method())) {
                return $frame;
            }
        }

        throw new UnexpectedFrame($frame->method(), ...$names);
    }

    public function close(): void
    {
        if (!$this->opened()) {
            return;
        }

        $this
            ->send($this->protocol->connection()->close(new Close))
            ->wait('connection.close-ok');
        $this->socket->close();
        $this->opened = false;
    }

    public function opened(): bool
    {
        return $this->opened && !$this->socket->closed();
    }

    public function __destruct()
    {
        $this->close();
    }

    private function buildSocket(): void
    {
        $this->socket = new Socket(
            $this->transport,
            $this->authority->withUserInformation(new NullUserInformation)
        );
        $this->select = (new Select($this->timeout))->forRead($this->socket);
    }

    private function open(): void
    {
        if ($this->opened()) {
            return;
        }

        $this->start();
        $this->handshake();
        $this->openVHost();

        $this->opened = true;
    }

    private function start(): void
    {
        $this->socket->write(
            new Str((string) $this->protocol->version())
        );

        try {
            $frame = $this->wait('connection.start');
        } catch (NoFrameDetected $e) {
            $content = $e->content()->toEncoding('ASCII');

            if (
                $content->length() !== 8 ||
                !$content->matches('/^AMQP/')
            ) {
                throw $e;
            }

            $version = $content
                ->substring(5, 8)
                ->chunk();
            $this->protocol->use(
                new Version(
                    UnsignedOctet::fromString($version->get(0))->original()->value(),
                    UnsignedOctet::fromString($version->get(1))->original()->value(),
                    UnsignedOctet::fromString($version->get(2))->original()->value()
                )
            );
            //socket rebuilt as the server close the connection on version mismatch
            $this->buildSocket();
            $this->start();

            return;
        }

        $this->send($this->protocol->connection()->startOk(
            new StartOk(
                $this->authority->userInformation()->user(),
                $this->authority->userInformation()->password()
            )
        ));
    }

    private function handshake(): void
    {
        $frame = $this->wait('connection.secure', 'connection.tune');

        if ($this->protocol->method('connection.secure')->equals($frame->method())) {
            $this->send($this->protocol->connection()->secureOk(
                new SecureOk(
                    $this->authority->userInformation()->user(),
                    $this->authority->userInformation()->password()
                )
            ));
            $frame = $this->wait('connection.tune');
        }

        $this->maxChannels = new MaxChannels(
            $frame->values()->get(0)->original()->value()
        );
        $this->maxFrameSize = new MaxFrameSize(
            $frame->values()->get(1)->original()->value()
        );
        $this->heartbeat = new ElapsedPeriod(
            $frame->values()->get(2)->original()->value()
        );
        $this->select = (new Select($this->heartbeat))->forRead($this->socket);
        $this->send($this->protocol->connection()->tuneOk(
            new TuneOk(
                $this->maxChannels,
                $this->maxFrameSize,
                $this->heartbeat
            )
        ));
    }

    private function openVHost(): void
    {
        $this
            ->send($this->protocol->connection()->open(
                new Open($this->vhost)
            ))
            ->wait('connection.open-ok');
    }
}