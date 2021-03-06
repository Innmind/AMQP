<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol\v091\Connection,
    Transport\Protocol\Connection as ConnectionInterface,
    Transport\Frame,
    Transport\Frame\Type,
    Transport\Frame\Method,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\Bits,
    Model\Connection\StartOk,
    Model\Connection\SecureOk,
    Model\Connection\TuneOk,
    Model\Connection\Open,
    Model\Connection\Close,
    Model\Connection\MaxChannels,
    Model\Connection\MaxFrameSize,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Url\{
    Authority\UserInformation\User,
    Authority\UserInformation\Password,
    Path,
};
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(ConnectionInterface::class, new Connection);
    }

    public function testStartOk()
    {
        $frame = (new Connection)->startOk(
            new StartOk(
                User::of('foo'),
                Password::of('bar')
            )
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame(0, $frame->channel()->toInt());
        $this->assertTrue($frame->is(new Method(10, 11)));
        $this->assertCount(4, $frame->values());
        $this->assertInstanceOf(Table::class, $frame->values()->get(0));
        $this->assertCount(6, $frame->values()->get(0)->original());
        $this->assertSame(
            'InnmindAMQP',
            $frame->values()->get(0)->original()->get('product')->original()->toString(),
        );
        $this->assertSame(
            'PHP',
            $frame->values()->get(0)->original()->get('platform')->original()->toString(),
        );
        $this->assertSame(
            '1.0',
            $frame->values()->get(0)->original()->get('version')->original()->toString(),
        );
        $this->assertSame(
            '',
            $frame->values()->get(0)->original()->get('information')->original()->toString(),
        );
        $this->assertSame(
            '',
            $frame->values()->get(0)->original()->get('copyright')->original()->toString(),
        );
        $this->assertCount(
            5,
            $frame->values()->get(0)->original()->get('capabilities')->original()
        );
        $this->assertTrue(
            $frame
                ->values()
                ->get(0)
                ->original()
                ->get('capabilities')
                ->original()
                ->get('authentication_failure_close')
                ->original()
                ->first()
        );
        $this->assertTrue(
            $frame
                ->values()
                ->get(0)
                ->original()
                ->get('capabilities')
                ->original()
                ->get('publisher_confirms')
                ->original()
                ->first()
        );
        $this->assertTrue(
            $frame
                ->values()
                ->get(0)
                ->original()
                ->get('capabilities')
                ->original()
                ->get('consumer_cancel_notify')
                ->original()
                ->first()
        );
        $this->assertTrue(
            $frame
                ->values()
                ->get(0)
                ->original()
                ->get('capabilities')
                ->original()
                ->get('exchange_exchange_bindings')
                ->original()
                ->first()
        );
        $this->assertTrue(
            $frame
                ->values()
                ->get(0)
                ->original()
                ->get('capabilities')
                ->original()
                ->get('connection.blocked')
                ->original()
                ->first()
        );
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('AMQPLAIN', $frame->values()->get(1)->original()->toString());
        $this->assertInstanceOf(LongString::class, $frame->values()->get(2));
        $this->assertSame(
            \chr(5).'LOGINS'.\pack('N', 3).'foo'.\chr(8).'PASSWORDS'.\pack('N', 3).'bar',
            $frame->values()->get(2)->original()->toString(),
        );
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(3));
        $this->assertSame('en_US', $frame->values()->get(3)->original()->toString());
    }

    public function testSecureOk()
    {
        $frame = (new Connection)->secureOk(
            new SecureOk(
                User::of('foo'),
                Password::of('bar')
            )
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame(0, $frame->channel()->toInt());
        $this->assertTrue($frame->is(new Method(10, 21)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(LongString::class, $frame->values()->get(0));
        $this->assertSame(
            \chr(5).'LOGINS'.\pack('N', 3).'foo'.\chr(8).'PASSWORDS'.\pack('N', 3).'bar',
            $frame->values()->get(0)->original()->toString(),
        );
    }

    public function testTuneOk()
    {
        $frame = (new Connection)->tuneOk(
            new TuneOk(
                new MaxChannels(1),
                new MaxFrameSize(10),
                new ElapsedPeriod(3000)
            )
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame(0, $frame->channel()->toInt());
        $this->assertTrue($frame->is(new Method(10, 31)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(1, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(
            UnsignedLongInteger::class,
            $frame->values()->get(1)
        );
        $this->assertSame(10, $frame->values()->get(1)->original()->value());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(2)
        );
        $this->assertSame(3, $frame->values()->get(2)->original()->value());
    }

    public function testOpen()
    {
        $frame = (new Connection)->open(
            new Open(Path::of('/'))
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame(0, $frame->channel()->toInt());
        $this->assertTrue($frame->is(new Method(10, 40)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(0)
        );
        $this->assertSame('/', $frame->values()->get(0)->original()->toString());
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(1)
        );
        $this->assertSame('', $frame->values()->get(1)->original()->toString());
        $this->assertInstanceOf(
            Bits::class,
            $frame->values()->get(2)
        );
        $this->assertFalse($frame->values()->get(2)->original()->first());
    }

    public function testClose()
    {
        $frame = (new Connection)->close(
            new Close
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame(0, $frame->channel()->toInt());
        $this->assertTrue($frame->is(new Method(10, 50)));
        $this->assertCount(4, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(1)
        );
        $this->assertSame('', $frame->values()->get(1)->original()->toString());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(2)
        );
        $this->assertSame(0, $frame->values()->get(2)->original()->value());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(3)
        );
        $this->assertSame(0, $frame->values()->get(3)->original()->value());

        $frame = (new Connection)->close(
            Close::reply(1, 'foo')->causedBy('connection.close')
        );

        $this->assertSame(1, $frame->values()->get(0)->original()->value());
        $this->assertSame('foo', $frame->values()->get(1)->original()->toString());
        $this->assertSame(10, $frame->values()->get(2)->original()->value());
        $this->assertSame(50, $frame->values()->get(3)->original()->value());
    }

    public function testCloseOk()
    {
        $frame = (new Connection)->closeOk();

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame(0, $frame->channel()->toInt());
        $this->assertTrue($frame->is(new Method(10, 51)));
        $this->assertCount(0, $frame->values());
    }
}
