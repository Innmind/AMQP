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
    private $connection;
    private $process;
    private $channels;
    private $channel = 1;

    public function __construct(Connection $connection, CurrentProcess $process)
    {
        $this->connection = $connection;
        $this->process = $process;
        $this->channels = new Map('int', Channel::class);
    }

    public function channel(): Channel
    {
        $pid = $this->process->id()->toInt();

        if ($this->channels->contains($pid)) {
            return $this->channels->get($pid);
        }

        $channel = new Channel\Channel(
            $this->connection,
            new Number($this->channel++)
        );
        $this->channels = $this->channels->put($pid, $channel);

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

        $this->channels->foreach(static function(int $pid, Channel $channel): void {
            $channel->close();
        });
        $this->connection->close();
    }
}
