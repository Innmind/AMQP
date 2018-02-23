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
    Command\Arguments,
    Command\Options,
    Environment,
};

final class Get implements Command
{
    private $client;
    private $consumers;

    public function __construct(Client $client, Consumers $consumers)
    {
        $this->client = $client;
        $this->consumers = $consumers;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $queue = $arguments->get('queue');
        $consume = $this->consumers->get($queue);
        $this
            ->client
            ->channel()
            ->basic()
            ->get(new Basic\Get($queue))($consume);
    }

    public function __toString(): string
    {
        return <<<USAGE
innmind:amqp:get queue

Will process a single message from the given queue
USAGE;
    }
}
