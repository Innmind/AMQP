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
    Command\Arguments,
    Command\Options,
    Environment,
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

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $queue = $arguments->get('queue');
        $consume = $this->consumers->get($queue);
        $basic = $this->client->channel()->basic();

        $this->qos($arguments, $basic);

        $consumer = $basic->consume(new Basic\Consume($queue));

        if ($arguments->contains('number')) {
            $consumer->take((int) $arguments->get('number'));
        }

        try {
            $consumer->foreach($consume);
        } finally {
            $this->client->close();
        }
    }

    public function toString(): string
    {
        return <<<USAGE
innmind:amqp:consume queue [number] [prefetch]

Will process messages from the given queue
USAGE;
    }

    private function qos(Arguments $arguments, Channel\Basic $basic): void
    {
        if ($arguments->contains('prefetch')) {
            $basic->qos(new Qos(0, (int) $arguments->get('prefetch')));

            return;
        }

        if ($arguments->contains('number')) {
            $basic->qos(new Qos(0, (int) $arguments->get('number')));

            return;
        }
    }
}
