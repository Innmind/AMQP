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
    /** @var callable(): Connection */
    private $load;
    private ?Connection $connection = null;
    private CurrentProcess $process;
    /** @var Map<int, Channel> */
    private Map $channels;
    private int $channel = 1;

    /**
     * @param callable(): Connection $load
     */
    public function __construct(callable $load, CurrentProcess $process)
    {
        $this->load = $load;
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

        /** @psalm-suppress ArgumentTypeCoercion */
        $channel = Channel\Channel::open(
            $this->connection(),
            new Number($this->channel++),
        );
        $this->channels = ($this->channels)($pid, $channel);

        return $channel;
    }

    public function closed(): bool
    {
        if (\is_null($this->connection)) {
            return false;
        }

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
        $this->connection()->close();
    }

    private function connection(): Connection
    {
        return $this->connection ??= ($this->load)();
    }
}
