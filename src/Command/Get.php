<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Client,
    Consumers,
    Model\Basic,
};
use Innmind\CLI\{
    Command,
    Console,
};

final class Get implements Command
{
    private Client $client;
    private Consumers $consumers;

    public function __construct(Client $client, Consumers $consumers)
    {
        $this->client = $client;
        $this->consumers = $consumers;
    }

    public function __invoke(Console $console): Console
    {
        $queue = $console->arguments()->get('queue');
        $consume = $this->consumers->get($queue);

        try {
            /** @psalm-suppress InvalidScalarArgument */
            $this
                ->client
                ->channel()
                ->basic()
                ->get(new Basic\Get($queue))($consume);

            return $console;
        } finally {
            $this->client->close();
        }
    }

    /**
     * @psalm-pure
     */
    public function usage(): string
    {
        return <<<USAGE
innmind:amqp:get queue

Will process a single message from the given queue
USAGE;
    }
}
