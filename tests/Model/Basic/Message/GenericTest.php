<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\{
    Message\Generic,
    Message,
    Message\ContentType,
    Message\ContentEncoding,
    Message\AppId,
    Message\CorrelationId,
    Message\DeliveryMode,
    Message\Id,
    Message\Priority,
    Message\ReplyTo,
    Message\Type,
    Message\UserId,
};
use Innmind\TimeContinuum\{
    PointInTime,
    Earth\ElapsedPeriod,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class GenericTest extends TestCase
{
    public function testInterface()
    {
        $message = new Generic(Str::of('foo'));

        $this->assertInstanceOf(Message::class, $message);
        $this->assertFalse($message->contentType()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->contentEncoding()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertInstanceOf(Map::class, $message->headers());
        $this->assertFalse($message->deliveryMode()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->priority()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->correlationId()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->replyTo()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->expiration()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->id()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->timestamp()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->type()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->userId()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($message->appId()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertInstanceOf(Str::class, $message->body());
        $this->assertSame('foo', $message->body()->toString());
        $this->assertSame('ASCII', $message->body()->encoding()->toString());
    }

    public function testContentType()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withContentType(
            $expected = new ContentType('text', 'plain'),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->contentType()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testContentEncoding()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withContentEncoding(
            $expected = new ContentEncoding('gzip'),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->contentEncoding()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testHeaders()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withHeaders(
            $expected = Map::of(['foo', 'bar']),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->headers());
    }

    public function testDeliveryMode()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withDeliveryMode(
            $expected = DeliveryMode::persistent,
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->deliveryMode()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testPriority()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withPriority(
            $expected = Priority::zero,
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->priority()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testCorrelationId()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withCorrelationId(
            $expected = new CorrelationId('foo'),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->correlationId()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testReplyTo()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withReplyTo(
            $expected = new ReplyTo('foo'),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->replyTo()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testExpiration()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withExpiration(
            $expected = new ElapsedPeriod(1000),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->expiration()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testId()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withId(
            $expected = new Id('foo'),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->id()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testTimestamp()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withTimestamp(
            $expected = $this->createMock(PointInTime::class),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->timestamp()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testType()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withType(
            $expected = new Type('foo'),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->type()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testUserId()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withUserId(
            $expected = new UserId('foo'),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->userId()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testAppId()
    {
        $message = new Generic(Str::of(''));
        $message2 = $message->withAppId(
            $expected = new AppId('foo'),
        );

        $this->assertInstanceOf(Message::class, $message2);
        $this->assertSame($expected, $message2->appId()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }
}
