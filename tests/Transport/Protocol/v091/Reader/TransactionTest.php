<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\Transport\{
    Protocol\v091\Reader\Transaction,
    Protocol\v091\Methods,
    Frame\Method,
    Frame\Value
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Str,
    StreamInterface
};
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInvokation($method, $arguments)
    {
        $read = new Transaction;

        $stream = $read(
            Methods::get($method),
            new StringStream(implode('', $arguments))
        );

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(Value::class, (string) $stream->type());
        $this->assertCount(count($arguments), $stream);

        foreach ($arguments as $i => $argument) {
            $this->assertInstanceOf(get_class($argument), $stream->get($i));
            $this->assertSame((string) $argument, (string) $stream->get($i));
        }
    }

    /**
     * @expectedException Innmind\AMQP\Exception\UnknownMethod
     * @expectedExceptionMessage 0,0
     */
    public function testThrowWhenUnknownMethod()
    {
        (new Transaction)(new Method(0, 0), new StringStream(''));
    }

    public function cases(): array
    {
        return [
            [
                'tx.select-ok',
                [],
            ],
            [
                'tx.commit-ok',
                [],
            ],
            [
                'tx.rollback-ok',
                [],
            ],
        ];
    }
}
