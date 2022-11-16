<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\Client as ClientInterface;
use Psr\Log\LoggerInterface;

final class Logger implements ClientInterface
{
    private ClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @return callable(ClientInterface): ClientInterface
     */
    public static function prepare(LoggerInterface $logger): callable
    {
        return static fn(ClientInterface $client): ClientInterface => new self($client, $logger);
    }

    public function channel(): Channel
    {
        return new Channel\Logger(
            $this->client->channel(),
            $this->logger,
        );
    }

    public function closed(): bool
    {
        return $this->client->closed();
    }

    public function close(): void
    {
        $this->client->close();
    }
}
