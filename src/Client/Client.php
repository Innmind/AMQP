<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client as ClientInterface,
    Transport\Connection,
    Transport\Frame\Channel as Number,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\Immutable\Map;

final class Client implements ClientInterface
{
    private Connection $connection;
    private CurrentProcess $process;
    /** @var Map<int, Channel> */
    private Map $channels;
    private int $channel = 1;

    public function __construct(Connection $connection, CurrentProcess $process)
    {
        $this->connection = $connection;
        $this->process = $process;
        /** @var Map<int, Channel> */
        $this->channels = Map::of();
    }

    public function channel(): Channel
    {
        $pid = $this->process->id()->toInt();

        if ($this->channels->contains($pid)) {
            return $this->channels->get($pid)->match(
                static fn($channel) => $channel,
                static fn() => throw new \LogicException,
            );
        }

        $channel = new Channel\Channel(
            $this->connection,
            new Number($this->channel++),
        );
        $this->channels = ($this->channels)($pid, $channel);

        return $channel;
    }

    public function closed(): bool
    {
        return $this->connection->closed();
    }

    public function close(): void
    {
        if ($this->closed()) {
            return;
        }

        $_ = $this->channels->foreach(static function(int $_, Channel $channel): void {
            $channel->close();
        });
        $this->connection->close();
    }
}
