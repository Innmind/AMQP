<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Connection\FrameReader,
    Transport\Protocol\Version,
    Model\Connection\StartOk,
    Model\Connection\SecureOk,
    Model\Connection\TuneOk,
    Model\Connection\Open,
    Model\Connection\Close,
    Model\Connection\MaxChannels,
    Model\Connection\MaxFrameSize,
    Exception\FrameChannelExceedAllowedChannelNumber,
    Exception\FrameExceedAllowedSize,
    Exception\UnexpectedFrame
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
        $this->socket = new Socket(
            $transport,
            $this->authority->withUserInformation(new NullUserInformation)
        );
        $this->select = (new Select($timeout))->forRead($this->socket);
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

    public function __destruct()
    {
        $this->close();
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

    private function close(): void
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

    private function opened(): bool
    {
        return $this->opened && !$this->socket->closed();
    }

    private function start(): void
    {
        $this->socket->write(
            new Str((string) $this->protocol->version())
        );

        $frame = $this->wait('connection.start');
        $this->protocol->use(
            new Version(
                $frame->values()->get(0)->original()->value(),
                $frame->values()->get(1)->original()->value(),
                0 //server doesn't provide bugfix version
            )
        );
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
