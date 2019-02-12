<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection as ConnectionInterface,
    Transport\Protocol,
    Transport\Frame,
    Model\Connection\MaxFrameSize,
};
use Ramsey\Uuid\Uuid;
use Psr\Log\LoggerInterface;

final class Logger implements ConnectionInterface
{
    private $connection;
    private $logger;

    public function __construct(
        ConnectionInterface $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function protocol(): Protocol
    {
        return $this->connection->protocol();
    }

    /**
     * {@inheritdoc}
     */
    public function send(Frame $frame): ConnectionInterface
    {
        $this->logger->debug(
            'AMQP frame about to be sent',
            [
                'type' => $frame->type()->toInt(),
                'channel' => $frame->channel()->toInt(),
                'uuid' => $uuid = (string) Uuid::uuid4(),
            ]
        );

        $this->connection->send($frame);
        $this->logger->debug('AMQP frame sent', ['uuid' => $uuid]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wait(string ...$names): Frame
    {
        $this->logger->debug('Waiting for AMQP frame', ['names' => $names]);
        $frame = $this->connection->wait(...$names);
        $this->logger->debug(
            'AMQP frame received',
            [
                'type' => $frame->type()->toInt(),
                'channel' => $frame->channel()->toInt(),
            ]
        );

        return $frame;
    }

    public function maxFrameSize(): MaxFrameSize
    {
        return $this->connection->maxFrameSize();
    }

    public function close(): void
    {
        $this->connection->close();
        $this->logger->debug('AMQP connection closed');
    }

    public function closed(): bool
    {
        return $this->connection->closed();
    }
}
