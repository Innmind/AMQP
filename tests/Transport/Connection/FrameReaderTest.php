<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\Transport\{
    Connection\FrameReader,
    Frame,
    Frame\Type,
    Frame\Channel,
    Frame\Method,
    Frame\Value,
    Frame\Value\UnsignedOctet,
    Frame\Value\Table,
    Frame\Value\LongString,
    Protocol\v091\Protocol
};
use Innmind\Stream\Readable\Stream;
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    Map
};
use PHPUnit\Framework\TestCase;

class FrameReaderTest extends TestCase
{
    public function testInvokation()
    {
        $read = new FrameReader;

        $file = tmpfile();
        fwrite(
            $file,
            (string) new Frame(
                Type::method(),
                new Channel(0),
                new Method(10, 10), // connection.start
                new UnsignedOctet(new Integer(0)),
                new UnsignedOctet(new Integer(9)),
                new Table(new Map('string', Value::class)),
                new LongString(new Str('AMQPLAIN')),
                new LongString(new Str('en_US'))
            )
        );
        fseek($file, 0);
        $stream = new Stream($file);

        $frame = $read($stream, new Protocol);

        $this->assertInstanceOf(Frame::class, $frame);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\ReceivedFrameNotDelimitedCorrectly
     */
    public function testThrowWhenFrameMissingEndMarker()
    {
        $read = new FrameReader;

        $file = tmpfile();
        $frame = (string) new Frame(
            Type::method(),
            new Channel(0),
            new Method(10, 10), // connection.start
            new UnsignedOctet(new Integer(0)),
            new UnsignedOctet(new Integer(9)),
            new Table(new Map('string', Value::class)),
            new LongString(new Str('AMQPLAIN')),
            new LongString(new Str('en_US'))
        );
        $frame = mb_substr($frame, 0, -4, 'ASCII'); //remove end marker
        fwrite($file, $frame);
        fseek($file, 0);
        $stream = new Stream($file);

        $read($stream, new Protocol);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\ReceivedFrameNotDelimitedCorrectly
     */
    public function testThrowWhenFrameEndMarkerInvalid()
    {
        $read = new FrameReader;

        $file = tmpfile();
        $frame = (string) new Frame(
            Type::method(),
            new Channel(0),
            new Method(10, 10), // connection.start
            new UnsignedOctet(new Integer(0)),
            new UnsignedOctet(new Integer(9)),
            new Table(new Map('string', Value::class)),
            new LongString(new Str('AMQPLAIN')),
            new LongString(new Str('en_US'))
        );
        $frame = mb_substr($frame, 0, -4, 'ASCII'); //remove end marker
        $frame .= new UnsignedOctet(new Integer(0xCD));
        fwrite($file, $frame);
        fseek($file, 0);
        $stream = new Stream($file);

        $read($stream, new Protocol);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\PayloadTooShort
     */
    public function testThrowWhenPayloadTooShort()
    {
        $read = new FrameReader;

        $file = tmpfile();
        $frame = (string) new Frame(
            Type::method(),
            new Channel(0),
            new Method(10, 10) // connection.start
        );
        $frame = mb_substr($frame, 0, -2, 'ASCII');
        fwrite($file, $frame);
        fseek($file, 0);
        $stream = new Stream($file);

        $read($stream, new Protocol);
    }
}
