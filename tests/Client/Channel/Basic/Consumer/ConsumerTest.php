<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Basic\Consumer;

use Innmind\AMQP\{
    Model\Exchange\Declaration as Exchange,
    Model\Exchange\Type,
    Model\Queue\Deletion,
    Model\Queue\Declaration as Queue,
    Model\Queue\Binding,
    Model\Basic\Message\Generic as Message,
    Model\Basic\Publish,
    Model\Basic\Qos,
    Model\Basic\Consume,
    Client,
};
use function Innmind\AMQP\bootstrap;
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
{
    public function testSegmentedConsumingDoesntAlterMessageOrdering()
    {
        $client = $this->client();
        $client
            ->channel()
            ->exchange()
            ->declare(
                Exchange::durable('e', Type::direct()),
            );
        $queue = $client
            ->channel()
            ->queue()
            ->declare(
                Queue::durable(),
            );
        $client
            ->channel()
            ->queue()
            ->bind(
                new Binding('e', $queue->name()),
            );

        foreach (\range(0, 99) as $i) {
            $message = new Message(Str::of("$i"));
            $client
                ->channel()
                ->basic()
                ->publish(
                    Publish::a($message)->to('e'),
                );
        }

        $client
            ->channel()
            ->basic()
            ->qos(new Qos(0, 10));
        $expected = [];

        foreach (\range(1, 10) as $_) {
            $consumer = $client
                ->channel()
                ->basic()
                ->consume(new Consume($queue->name()));
            $consumer->take(10);
            $consumer->foreach(static function($message) use (&$expected) {
                $expected[] = (int) $message->body()->toString();
            });
        }

        $this->assertSame(\range(0, 99), $expected);
    }

    public function testSegmentedConsumingDoesntAlterMessageOrderingBetweenConnections()
    {
        $client = $this->client();
        $client
            ->channel()
            ->exchange()
            ->declare(
                Exchange::durable('e', Type::direct()),
            );
        $queue = $client
            ->channel()
            ->queue()
            ->declare(
                Queue::durable(),
            );
        $client
            ->channel()
            ->queue()
            ->bind(
                new Binding('e', $queue->name()),
            );

        foreach (\range(0, 99) as $i) {
            $message = new Message(Str::of("$i"));
            $client
                ->channel()
                ->basic()
                ->publish(
                    Publish::a($message)->to('e'),
                );
        }

        $client->close();
        $expected = [];

        foreach (\range(1, 10) as $_) {
            $client = $this->client();
            $client
                ->channel()
                ->basic()
                ->qos(new Qos(0, 10));
            $consumer = $client
                ->channel()
                ->basic()
                ->consume(new Consume($queue->name()));
            $consumer->take(10);
            $consumer->foreach(static function($message) use (&$expected) {
                $expected[] = (int) $message->body()->toString();
            });
            $client->close();
        }

        $this->assertSame(\range(0, 99), $expected);
    }
    protected function client(): Client
    {
        $os = Factory::build();
        $amqp = bootstrap();

        return $amqp['client']['basic'](
            Transport::tcp(),
            Url::of('amqp://guest:guest@localhost:5672/'),
            new ElapsedPeriod(1000), // timeout
            $os->clock(),
            $os->process(),
            $os->remote(),
            $os->sockets(),
        );
    }
}
