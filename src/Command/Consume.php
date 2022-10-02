<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Client,
    Client\Channel,
    Consumers,
    Model\Basic,
    Model\Basic\Qos,
};
use Innmind\CLI\{
    Command,
    Console,
};

final class Consume implements Command
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
        $basic = $this->client->channel()->basic();

        $this->qos($console, $basic);

        $consumer = $basic->consume(new Basic\Consume($queue));

        if ($console->arguments()->contains('number')) {
            $consumer->take((int) $console->arguments()->get('number'));
        }

        try {
            $consumer->foreach($consume);

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
innmind:amqp:consume queue [number] [prefetch]

Will process messages from the given queue
USAGE;
    }

    private function qos(Console $console, Channel\Basic $basic): void
    {
        if ($console->arguments()->contains('prefetch')) {
            $basic->qos(new Qos(0, (int) $console->arguments()->get('prefetch')));

            return;
        }

        if ($console->arguments()->contains('number')) {
            $basic->qos(new Qos(0, (int) $console->arguments()->get('number')));

            return;
        }
    }
}
