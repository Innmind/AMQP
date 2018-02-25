<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client,
    Model\Exchange\Declaration as Exchange,
    Model\Queue\Declaration as Queue,
    Model\Queue\Binding,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class AutoDeclare implements Client
{
    private $client;
    private $exchanges;
    private $queues;
    private $bindings;
    private $declared = false;

    /**
     * @param SetInterface<Exchange>|null $exchanges
     * @param SetInterface<Queue>|null $queues
     * @param SetInterface<Binding>|null $bindings
     */
    public function __construct(
        Client $client,
        SetInterface $exchanges = null,
        SetInterface $queues = null,
        SetInterface $bindings = null
    ) {
        $this->client = $client;
        $this->exchanges = $exchanges ?? Set::of(Exchange::class);
        $this->queues = $queues ?? Set::of(Queue::class);
        $this->bindings = $bindings ?? Set::of(Binding::class);

        if ((string) $this->exchanges->type() !== Exchange::class) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type SetInterface<%s>',
                Exchange::class
            ));
        }

        if ((string) $this->queues->type() !== Queue::class) {
            throw new \TypeError(sprintf(
                'Argument 3 must be of type SetInterface<%s>',
                Queue::class
            ));
        }

        if ((string) $this->bindings->type() !== Binding::class) {
            throw new \TypeError(sprintf(
                'Argument 4 must be of type SetInterface<%s>',
                Binding::class
            ));
        }
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
