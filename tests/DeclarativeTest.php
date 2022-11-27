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
    Command\Consume,
    Model\Exchange\Type,
    Model\Basic,
    Exception\BasicGetNotCancellable,
};
use Innmind\Socket\Internet\Transport;
use Innmind\OperatingSystem\Factory;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Str,
    Sequence,
};
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
                    ->take(3) // the third attempt will return a basic.get-empty and the handler is not called
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

    public function testCancelingAGetThrowsAnException()
    {
        $this->expectException(BasicGetNotCancellable::class);

        $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message')))
                    ->to('foo'),
            ))
            ->with(
                Get::of('bar')
                    ->take(3)
                    ->handle(function($state, $message, $continuation, $details) {
                        $this->assertSame('message', $message->body()->toString());

                        return $continuation->cancel($state);
                    }),
            )
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(null);
    }

    public function testConsume()
    {
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Qos::of(10))
            ->with(Publish::many(Sequence::of(
                Basic\Publish::a(Basic\Message::of(Str::of('message0')))
                    ->to('foo'),
                Basic\Publish::a(Basic\Message::of(Str::of('message1')))
                    ->to('foo'),
                Basic\Publish::a(Basic\Message::of(Str::of('message2')))
                    ->to('foo'),
                Basic\Publish::a(Basic\Message::of(Str::of('message3')))
                    ->to('foo'),
            )))
            ->with(
                Consume::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertSame('message'.$state, $message->body()->toString());

                    if ($state === 1) {
                        // we don't increment the state because the message with
                        // the content "message1" will be requeued and will be
                        // delivered in the second consumer below
                        return $continuation->cancel($state);
                    }

                    return $continuation->ack($state + 1);
                }),
            )
            // this second consume proves that the consumer above correctly
            // recovered the prefetched messages, otherwise the messages would
            // be "nowhere" and the consumer below would hang forever
            ->with(
                Consume::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertSame('message'.$state, $message->body()->toString());

                    if ($state === 3) {
                        return $continuation->cancel($state + 1);
                    }

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

        $this->assertSame(4, $result);
    }

    public function testReject()
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

                    return $continuation->reject('rejected');
                }),
            )
            ->with(
                Get::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertFalse(true, 'The second handler should not be called');

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

        $this->assertSame('rejected', $result);
    }
}
