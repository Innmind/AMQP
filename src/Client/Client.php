<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client as ClientInterface,
    Transport\Connection,
    Transport\Frame\Channel as Number
};
use Innmind\Immutable\Map;

final class Client implements ClientInterface
{
    private $connection;
    private $channels;
    private $channel = 1;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->channels = new Map('int', Channel::class);
    }

    public function channel(): Channel
    {
        $pid = getmypid();

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
        return !$this->connection->opened();
    }

    public function close(): void
    {
        if ($this->closed()) {
            return;
        }

        $this->channels->foreach(static function(Channel $channel): void {
            $channel->close();
        });
        $this->connection->close();
    }
}
