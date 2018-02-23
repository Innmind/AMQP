<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Client,
    Model\Queue,
    Exception\UnexpectedFrame,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Immutable\Str;

final class Purge implements Command
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $queue = $arguments->get('queue');

        try {
            $this
                ->client
                ->channel()
                ->queue()
                ->purge(new Queue\Purge($queue));
        } catch (UnexpectedFrame $e) {
            $env->error()->write(Str::of("Purging \"$queue\" failed"));
            $env->exit(1);
        } finally {
            $this->client->close();
        }
    }

    public function __toString(): string
    {
        return <<<USAGE
innmind:amqp:purge queue

Will delete all messages for the given queue
USAGE;
    }
}
