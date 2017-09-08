<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection\FrameReader,
    Transport\Frame,
    Transport\Frame\Type,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\LongString,
    Transport\Protocol\v091\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Exception\NoFrameDetected
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
    private $protocol;

    public function setUp()
    {
        $this->protocol = new Protocol($this->createMock(ArgumentTranslator::class));
    }

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

        $frame = $read($stream, $this->protocol);

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

        $read($stream, $this->protocol);
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

        $read($stream, $this->protocol);
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

        $read($stream, $this->protocol);
    }

    public function testThrowWhenNoFrameDeteted()
    {
        $file = tmpfile();
        fwrite($file, $content = "AMQP\x00\x00\x09\x01");
        fseek($file, 0);
        $stream = new Stream($file);

        try {
            (new FrameReader)($stream, $this->protocol);
            $this->fail('it should throw an exception');
        } catch (NoFrameDetected $e) {
            $this->assertInstanceOf(Str::class, $e->content());
            $this->assertSame($content, (string) $e->content());
        }
    }
}
