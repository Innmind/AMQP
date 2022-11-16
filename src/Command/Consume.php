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

        $consumer = $basic->consume(Basic\Consume::of($queue));

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
            $prefetch = (int) $console->arguments()->get('prefetch');

            if ($prefetch < 0 || $prefetch > 65535) {
                return;
            }

            /** @psalm-suppress InvalidArgument */
            $basic->qos(Qos::of(0, $prefetch));

            return;
        }

        if ($console->arguments()->contains('number')) {
            $prefetch = (int) $console->arguments()->get('number');

            if ($prefetch < 0 || $prefetch > 65535) {
                return;
            }

            /** @psalm-suppress InvalidArgument */
            $basic->qos(Qos::of(0, $prefetch));

            return;
        }
    }
}
