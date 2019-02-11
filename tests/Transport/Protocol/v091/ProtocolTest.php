<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol\v091\Protocol,
    Transport\Protocol\v091\Connection,
    Transport\Protocol\v091\Channel,
    Transport\Protocol\v091\Exchange,
    Transport\Protocol\v091\Queue,
    Transport\Protocol\v091\Basic,
    Transport\Protocol\v091\Transaction,
    Transport\Protocol as ProtocolInterface,
    Transport\Protocol\Version,
    Transport\Protocol\ArgumentTranslator,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Frame\Method,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Value,
    Transport\Frame\Value\ShortString,
    Model\Basic\Publish,
    Model\Basic\Message\Generic,
    Model\Basic\Message\AppId,
    Model\Basic\Message\ContentEncoding,
    Model\Basic\Message\ContentType,
    Model\Basic\Message\CorrelationId,
    Model\Basic\Message\DeliveryMode,
    Model\Basic\Message\Id,
    Model\Basic\Message\Priority,
    Model\Basic\Message\ReplyTo,
    Model\Basic\Message\Type,
    Model\Basic\Message\UserId,
    Model\Connection\MaxFrameSize
};
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    PointInTime\Earth\Now
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Str,
    Map,
    StreamInterface
};
use PHPUnit\Framework\TestCase;

class ProtocolTest extends TestCase
{
    public function testInterface()
    {
        $protocol = new Protocol($this->createMock(ArgumentTranslator::class));

        $this->assertInstanceOf(ProtocolInterface::class, $protocol);
        $this->assertInstanceOf(Version::class, $protocol->version());
        $this->assertSame("AMQP\x00\x00\x09\x01", (string) $protocol->version());
        $this->assertInstanceOf(Connection::class, $protocol->connection());
        $this->assertInstanceOf(Channel::class, $protocol->channel());
        $this->assertInstanceOf(Exchange::class, $protocol->exchange());
        $this->assertInstanceOf(Queue::class, $protocol->queue());
        $this->assertInstanceOf(Basic::class, $protocol->basic());
        $this->assertInstanceOf(Transaction::class, $protocol->transaction());
    }

    /**
     * @dataProvider methods
     */
    public function testMethod($name, $class, $method)
    {
        $protocol = new Protocol($this->createMock(ArgumentTranslator::class));

        $this->assertInstanceOf(Method::class, $protocol->method($name));
        $this->assertTrue($protocol->method($name)->equals(new Method($class, $method)));
    }

    public function testUse()
    {
        $protocol = new Protocol($this->createMock(ArgumentTranslator::class));

        $this->assertSame($protocol, $protocol->use(new Version(0, 9, 0)));
        $this->assertSame($protocol, $protocol->use(new Version(0, 9, 1)));
    }

    public function testReadHeader()
    {
        $protocol = new Protocol(new ValueTranslator);

        $header = $protocol
            ->basic()
            ->publish(
                new FrameChannel(1),
                new Publish(
                    (new Generic(new Str('foobar')))
                        ->withContentType(new ContentType('application', 'json'))
                        ->withContentEncoding(new ContentEncoding('gzip'))
                        ->withHeaders(
                            (new Map('string', 'mixed'))
                                ->put('foo', new ShortString(new Str('bar')))
                        )
                        ->withDeliveryMode(DeliveryMode::persistent())
                        ->withPriority(new Priority(5))
                        ->withCorrelationId(new CorrelationId('correlation'))
                        ->withReplyTo(new ReplyTo('reply'))
                        ->withExpiration(new ElapsedPeriod(1000))
                        ->withId(new Id('id'))
                        ->withTimestamp($now = new Now)
                        ->withType(new Type('type'))
                        ->withUserId(new UserId('guest'))
                        ->withAppId(new AppId('webcrawler'))
                ),
                new MaxFrameSize(10)
            )
            ->get(1);

        $values = $protocol->readHeader(new StringStream((string) $header->values()->join('')));

        $this->assertInstanceOf(StreamInterface::class, $values);
        $this->assertSame(Value::class, (string) $values->type());
        $this->assertCount(15, $values); // body size + flag bits + 13 properties
        $this->assertSame(
            (string) $values->join(''),
            (string) $header->values()->join('')
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\VersionNotUsable
     * @expectedExceptionMessage 1.0.0
     */
    public function testThrowWhenUsingHigherVersion()
    {
        (new Protocol($this->createMock(ArgumentTranslator::class)))->use(new Version(1, 0, 0));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\VersionNotUsable
     * @expectedExceptionMessage 0.8.0
     */
    public function testThrowWhenUsingLowerVersion()
    {
        (new Protocol($this->createMock(ArgumentTranslator::class)))->use(new Version(0, 8, 0));
    }

    public function methods(): array
    {
        return [
            ['connection.start',  10, 10],
            ['connection.start-ok', 10, 11],
            ['connection.secure',  10, 20],
            ['connection.secure-ok', 10, 21],
            ['connection.tune',  10, 30],
            ['connection.tune-ok', 10, 31],
            ['connection.open',  10, 40],
            ['connection.open-ok', 10, 41],
            ['connection.close',  10, 50],
            ['connection.close-ok', 10, 51],
            ['channel.open',  20, 10],
            ['channel.open-ok', 20, 11],
            ['channel.flow',  20, 20],
            ['channel.flow-ok', 20, 21],
            ['channel.close',  20, 40],
            ['channel.close-ok', 20, 41],
            ['exchange.declare',  40, 10],
            ['exchange.declare-ok', 40, 11],
            ['exchange.delete',  40, 20],
            ['exchange.delete-ok', 40, 21],
            ['queue.declare',  50, 10],
            ['queue.declare-ok', 50, 11],
            ['queue.bind',  50, 20],
            ['queue.bind-ok', 50, 21],
            ['queue.unbind',  50, 50],
            ['queue.unbind-ok', 50, 51],
            ['queue.purge',  50, 30],
            ['queue.purge-ok', 50, 31],
            ['queue.delete',  50, 40],
            ['queue.delete-ok', 50, 41],
            ['basic.qos',  60, 10],
            ['basic.qos-ok', 60, 11],
            ['basic.consume',  60, 20],
            ['basic.consume-ok', 60, 21],
            ['basic.cancel',  60, 30],
            ['basic.cancel-ok', 60, 31],
            ['basic.publish',  60, 40],
            ['basic.return',  60, 50],
            ['basic.deliver',  60, 60],
            ['basic.get',  60, 70],
            ['basic.get-ok', 60, 71],
            ['basic.get-empty', 60, 72],
            ['basic.ack',  60, 80],
            ['basic.reject',  60, 90],
            ['basic.recover-async', 60, 100],
            ['basic.recover',  60, 110],
            ['basic.recover-ok', 60, 111],
            ['tx.select',  90, 10],
            ['tx.select-ok', 90, 11],
            ['tx.commit',  90, 20],
            ['tx.commit-ok', 90, 21],
            ['tx.rollback',  90, 30],
            ['tx.rollback-ok', 90, 31],
        ];
    }
}
