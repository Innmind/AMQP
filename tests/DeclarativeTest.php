<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use Innmind\AMQP\{
    Declarative,
    Transport\Connection,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Protocol,
    Command\DeclareExchange,
    Command\DeleteExchange,
    Command\DeclareQueue,
    Command\DeleteQueue,
    Command\Bind,
    Command\Unbind,
    Command\Purge,
    Command\Qos,
    Command\Publish,
    Command\Get,
    Model\Exchange\Type,
    Model\Basic,
};
use Innmind\Socket\Internet\Transport;
use Innmind\OperatingSystem\Factory;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Url\Url;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class DeclarativeTest extends TestCase
{
    private Declarative $client;

    public function setUp(): void
    {
        $os = Factory::build();

        $this->client = Declarative::of(
            static fn() => Connection::open(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol($os->clock(), new ValueTranslator),
                new ElapsedPeriod(1000),
                $os->clock(),
                $os->remote(),
                $os->sockets(),
            ),
        );
    }

    public function testDeclareExchange()
    {
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Qos::of(10))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message')))
                    ->to('foo'),
            ))
            ->with(
                Get::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertNull($state);
                    $this->assertFalse($details->redelivered());
                    $this->assertSame('foo', $details->exchange());
                    $this->assertSame('', $details->routingKey());
                    $this->assertSame('message', $message->body()->toString());

                    return $continuation->requeue('requeued');
                }),
            )
            ->with(
                Get::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertSame('requeued', $state);
                    $this->assertTrue($details->redelivered());
                    $this->assertSame('foo', $details->exchange());
                    $this->assertSame('', $details->routingKey());
                    $this->assertSame('message', $message->body()->toString());

                    return $continuation->ack($message->body()->toString());
                }),
            )
            ->with(Get::of('bar'))
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(null)
            ->match(
                static fn($state) => $state,
                static fn($error) => $error,
            );

        $this->assertSame('message', $result);
    }

    public function testMultipleGet()
    {
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message0')))
                    ->to('foo'),
            ))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message1')))
                    ->to('foo'),
            ))
            ->with(
                Get::of('bar')
                    ->take(3)
                    ->handle(function($state, $message, $continuation, $details) {
                        $this->assertSame('message'.$state, $message->body()->toString());

                        return $continuation->ack($state + 1);
                    }),
            )
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(0)
            ->match(
                static fn($state) => $state,
                static fn($error) => $error,
            );

        $this->assertSame(2, $result);
    }
}
