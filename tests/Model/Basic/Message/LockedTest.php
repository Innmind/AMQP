<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\{
    Model\Basic\Message\Locked,
    Model\Basic\Message\Generic,
    Model\Basic\Message,
    Model\Basic\Message\ContentType,
    Model\Basic\Message\ContentEncoding,
    Model\Basic\Message\AppId,
    Model\Basic\Message\CorrelationId,
    Model\Basic\Message\DeliveryMode,
    Model\Basic\Message\Id,
    Model\Basic\Message\Priority,
    Model\Basic\Message\ReplyTo,
    Model\Basic\Message\Type,
    Model\Basic\Message\UserId,
    Exception\MessageLocked,
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

class LockedTest extends TestCase
{
    public function testInterface()
    {
        $message = new Locked(new Generic(Str::of('foo')));

        $this->assertInstanceOf(Message::class, $message);
        $this->assertFalse($message->hasContentType());
        $this->assertFalse($message->hasContentEncoding());
        $this->assertFalse($message->hasHeaders());
        $this->assertFalse($message->hasDeliveryMode());
        $this->assertFalse($message->hasPriority());
        $this->assertFalse($message->hasCorrelationId());
        $this->assertFalse($message->hasReplyTo());
        $this->assertFalse($message->hasExpiration());
        $this->assertFalse($message->hasId());
        $this->assertFalse($message->hasTimestamp());
        $this->assertFalse($message->hasType());
        $this->assertFalse($message->hasUserId());
        $this->assertFalse($message->hasAppId());
        $this->assertInstanceOf(Str::class, $message->body());
        $this->assertSame('foo', $message->body()->toString());
        $this->assertSame('ASCII', $message->body()->encoding()->toString());
    }

    public function testContentType()
    {
        $message = (new Generic(Str::of('')))->withContentType(
            $expected = new ContentType('text', 'plain'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasContentType());
        $this->assertSame($expected, $message->contentType());
    }

    public function testThrowWhenAddingContentType()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withContentType(
            new ContentType('text', 'plain'),
        );
    }

    public function testContentEncoding()
    {
        $message = (new Generic(Str::of('')))->withContentEncoding(
            $expected = new ContentEncoding('gzip'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasContentEncoding());
        $this->assertSame($expected, $message->contentEncoding());
    }

    public function testThrowWhenAddingContentEncoding()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withContentEncoding(
            new ContentEncoding('gzip'),
        );
    }

    public function testHeaders()
    {
        $message = (new Generic(Str::of('')))->withHeaders(
            $expected = Map::of('string', 'mixed')
                ('foo', 'bar'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasHeaders());
        $this->assertSame($expected, $message->headers());
    }

    public function testThrowWhenAddingHeaders()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withHeaders(
            Map::of('string', 'mixed'),
        );
    }

    public function testDeliveryMode()
    {
        $message = (new Generic(Str::of('')))->withDeliveryMode(
            $expected = DeliveryMode::persistent,
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasDeliveryMode());
        $this->assertSame($expected, $message->deliveryMode());
    }

    public function testThrowWhenAddingDeliveryMode()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withDeliveryMode(
            DeliveryMode::nonPersistent,
        );
    }

    public function testPriority()
    {
        $message = (new Generic(Str::of('')))->withPriority(
            $expected = Priority::zero,
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasPriority());
        $this->assertSame($expected, $message->priority());
    }

    public function testThrowWhenAddingPriority()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withPriority(
            Priority::five,
        );
    }

    public function testCorrelationId()
    {
        $message = (new Generic(Str::of('')))->withCorrelationId(
            $expected = new CorrelationId('foo'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasCorrelationId());
        $this->assertSame($expected, $message->correlationId());
    }

    public function testThrowWhenAddingCorrelationId()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withCorrelationId(
            new CorrelationId(''),
        );
    }

    public function testReplyTo()
    {
        $message = (new Generic(Str::of('')))->withReplyTo(
            $expected = new ReplyTo('foo'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasReplyTo());
        $this->assertSame($expected, $message->replyTo());
    }

    public function testThrowWhenAddingReplyTo()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withReplyTo(
            new ReplyTo(''),
        );
    }

    public function testExpiration()
    {
        $message = (new Generic(Str::of('')))->withExpiration(
            $expected = new ElapsedPeriod(1000),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasExpiration());
        $this->assertSame($expected, $message->expiration());
    }

    public function testThrowWhenAddingExpiration()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withExpiration(
            new ElapsedPeriod(1000),
        );
    }

    public function testId()
    {
        $message = (new Generic(Str::of('')))->withId(
            $expected = new Id('foo'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasId());
        $this->assertSame($expected, $message->id());
    }

    public function testThrowWhenAddingId()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withId(
            new Id(''),
        );
    }

    public function testTimestamp()
    {
        $message = (new Generic(Str::of('')))->withTimestamp(
            $expected = $this->createMock(PointInTime::class),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasTimestamp());
        $this->assertSame($expected, $message->timestamp());
    }

    public function testThrowWhenAddingTimestamp()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withTimestamp(
            $this->createMock(PointInTime::class),
        );
    }

    public function testType()
    {
        $message = (new Generic(Str::of('')))->withType(
            $expected = new Type('foo'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasType());
        $this->assertSame($expected, $message->type());
    }

    public function testThrowWhenAddingType()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withType(
            new Type(''),
        );
    }

    public function testUserId()
    {
        $message = (new Generic(Str::of('')))->withUserId(
            $expected = new UserId('foo'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasUserId());
        $this->assertSame($expected, $message->userId());
    }

    public function testThrowWhenAddingUserId()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withUserId(
            new UserId(''),
        );
    }

    public function testAppId()
    {
        $message = (new Generic(Str::of('')))->withAppId(
            $expected = new AppId('foo'),
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasAppId());
        $this->assertSame($expected, $message->appId());
    }

    public function testThrowWhenAddingAppId()
    {
        $this->expectException(MessageLocked::class);

        (new Locked(new Generic(Str::of(''))))->withAppId(
            new AppId(''),
        );
    }
}
