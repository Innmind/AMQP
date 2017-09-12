<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\{
    Message\Locked,
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
    Message\UserId
};
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Immutable\{
    Map,
    Str
};
use PHPUnit\Framework\TestCase;

class LockedTest extends TestCase
{
    public function testInterface()
    {
        $message = new Locked(new Generic(new Str('foo')));

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
        $this->assertSame('foo', (string) $message->body());
        $this->assertSame('ASCII', (string) $message->body()->encoding());
    }

    public function testContentType()
    {
        $message = (new Generic(new Str('')))->withContentType(
            $expected = new ContentType('text', 'plain')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasContentType());
        $this->assertSame($expected, $message->contentType());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingContentType()
    {
        (new Locked(new Generic(new Str(''))))->withContentType(
            new ContentType('text', 'plain')
        );
    }

    public function testContentEncoding()
    {
        $message = (new Generic(new Str('')))->withContentEncoding(
            $expected = new ContentEncoding('gzip')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasContentEncoding());
        $this->assertSame($expected, $message->contentEncoding());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingContentEncoding()
    {
        (new Locked(new Generic(new Str(''))))->withContentEncoding(
            new ContentEncoding('gzip')
        );
    }

    public function testHeaders()
    {
        $message = (new Generic(new Str('')))->withHeaders(
            $expected = (new Map('string', 'mixed'))
                ->put('foo', 'bar')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasHeaders());
        $this->assertSame($expected, $message->headers());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingHeaders()
    {
        (new Locked(new Generic(new Str(''))))->withHeaders(
            new Map('string', 'mixed')
        );
    }

    public function testDeliveryMode()
    {
        $message = (new Generic(new Str('')))->withDeliveryMode(
            $expected = DeliveryMode::persistent()
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasDeliveryMode());
        $this->assertSame($expected, $message->deliveryMode());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingDeliveryMode()
    {
        (new Locked(new Generic(new Str(''))))->withDeliveryMode(
            DeliveryMode::nonPersistent()
        );
    }

    public function testPriority()
    {
        $message = (new Generic(new Str('')))->withPriority(
            $expected = new Priority(0)
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasPriority());
        $this->assertSame($expected, $message->priority());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingPriority()
    {
        (new Locked(new Generic(new Str(''))))->withPriority(
            new Priority(5)
        );
    }

    public function testCorrelationId()
    {
        $message = (new Generic(new Str('')))->withCorrelationId(
            $expected = new CorrelationId('foo')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasCorrelationId());
        $this->assertSame($expected, $message->correlationId());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingCorrelationId()
    {
        (new Locked(new Generic(new Str(''))))->withCorrelationId(
            new CorrelationId('')
        );
    }

    public function testReplyTo()
    {
        $message = (new Generic(new Str('')))->withReplyTo(
            $expected = new ReplyTo('foo')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasReplyTo());
        $this->assertSame($expected, $message->replyTo());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingReplyTo()
    {
        (new Locked(new Generic(new Str(''))))->withReplyTo(
            new ReplyTo('')
        );
    }

    public function testExpiration()
    {
        $message = (new Generic(new Str('')))->withExpiration(
            $expected = new ElapsedPeriod(1000)
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasExpiration());
        $this->assertSame($expected, $message->expiration());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingExpiration()
    {
        (new Locked(new Generic(new Str(''))))->withExpiration(
            new ElapsedPeriod(1000)
        );
    }

    public function testId()
    {
        $message = (new Generic(new Str('')))->withId(
            $expected = new Id('foo')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasId());
        $this->assertSame($expected, $message->id());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingId()
    {
        (new Locked(new Generic(new Str(''))))->withId(
            new Id('')
        );
    }

    public function testTimestamp()
    {
        $message = (new Generic(new Str('')))->withTimestamp(
            $expected = $this->createMock(PointInTimeInterface::class)
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasTimestamp());
        $this->assertSame($expected, $message->timestamp());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingTimestamp()
    {
        (new Locked(new Generic(new Str(''))))->withTimestamp(
            $this->createMock(PointInTimeInterface::class)
        );
    }

    public function testType()
    {
        $message = (new Generic(new Str('')))->withType(
            $expected = new Type('foo')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasType());
        $this->assertSame($expected, $message->type());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingType()
    {
        (new Locked(new Generic(new Str(''))))->withType(
            new Type('')
        );
    }

    public function testUserId()
    {
        $message = (new Generic(new Str('')))->withUserId(
            $expected = new UserId('foo')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasUserId());
        $this->assertSame($expected, $message->userId());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingUserId()
    {
        (new Locked(new Generic(new Str(''))))->withUserId(
            new UserId('')
        );
    }

    public function testAppId()
    {
        $message = (new Generic(new Str('')))->withAppId(
            $expected = new AppId('foo')
        );
        $message = new Locked($message);

        $this->assertTrue($message->hasAppId());
        $this->assertSame($expected, $message->appId());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\MessageLocked
     */
    public function testThrowWhenAddingAppId()
    {
        (new Locked(new Generic(new Str(''))))->withAppId(
            new AppId('')
        );
    }
}
