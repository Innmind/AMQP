<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client,
    Model\Exchange\Declaration as Exchange,
    Model\Queue\Declaration as Queue,
    Model\Queue\Binding,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\assertSet;

final class AutoDeclare implements Client
{
    private Client $client;
    /** @var Set<Exchange> */
    private Set $exchanges;
    /** @var Set<Queue> */
    private Set $queues;
    /** @var Set<Binding> */
    private Set $bindings;
    private bool $declared = false;

    /**
     * @param Set<Exchange>|null $exchanges
     * @param Set<Queue>|null $queues
     * @param Set<Binding>|null $bindings
     */
    public function __construct(
        Client $client,
        Set $exchanges = null,
        Set $queues = null,
        Set $bindings = null,
    ) {
        $this->client = $client;
        $this->exchanges = $exchanges ?? Set::of(Exchange::class);
        $this->queues = $queues ?? Set::of(Queue::class);
        $this->bindings = $bindings ?? Set::of(Binding::class);

        assertSet(Exchange::class, $this->exchanges, 2);
        assertSet(Queue::class, $this->queues, 3);
        assertSet(Binding::class, $this->bindings, 4);
    }

    public function channel(): Channel
    {
        $channel = $this->client->channel();
        $this->declareThrough($channel);

        return $channel;
    }

    public function closed(): bool
    {
        return $this->client->closed();
    }

    public function close(): void
    {
        $this->client->close();
        $this->declared = true;
    }

    private function declareThrough(Channel $channel): void
    {
        if ($this->declared) {
            return;
        }

        $exchange = $channel->exchange();
        $queue = $channel->queue();

        $this->exchanges->foreach(static function(Exchange $command) use ($exchange): void {
            $exchange->declare($command);
        });
        $this->queues->foreach(static function(Queue $command) use ($queue): void {
            $queue->declare($command);
        });
        $this->bindings->foreach(static function(Binding $command) use ($queue): void {
            $queue->bind($command);
        });
        $this->declared = true;
    }
}
