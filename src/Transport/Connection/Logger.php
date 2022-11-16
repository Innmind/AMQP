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
    private ConnectionInterface $connection;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        LoggerInterface $logger,
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @return callable(ConnectionInterface): ConnectionInterface
     */
    public static function prepare(LoggerInterface $logger): callable
    {
        return static fn(ConnectionInterface $connection): ConnectionInterface => new self($connection, $logger);
    }

    public function protocol(): Protocol
    {
        return $this->connection->protocol();
    }

    public function send(Frame $frame): void
    {
        $this->logger->debug(
            'AMQP frame about to be sent',
            [
                'type' => $frame->type()->toInt(),
                'channel' => $frame->channel()->toInt(),
                'uuid' => $uuid = Uuid::uuid4()->toString(),
            ],
        );

        $this->connection->send($frame);
        $this->logger->debug('AMQP frame sent', ['uuid' => $uuid]);
    }

    public function wait(Frame\Method ...$names): Frame
    {
        $this->logger->debug('Waiting for AMQP frame', ['names' => $names]);
        $frame = $this->connection->wait(...$names);
        $this->logger->debug(
            'AMQP frame received',
            [
                'type' => $frame->type()->toInt(),
                'channel' => $frame->channel()->toInt(),
            ],
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
