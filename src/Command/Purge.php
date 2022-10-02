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
    Console,
};
use Innmind\Immutable\Str;

final class Purge implements Command
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __invoke(Console $console): Console
    {
        $queue = $console->arguments()->get('queue');

        try {
            $this
                ->client
                ->channel()
                ->queue()
                ->purge(new Queue\Purge($queue));

            return $console;
        } catch (UnexpectedFrame $e) {
            return $console
                ->error(Str::of("Purging \"$queue\" failed"))
                ->exit(1);
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
innmind:amqp:purge queue

Will delete all messages for the given queue
USAGE;
    }
}
