<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\Reader;

use Innmind\AMQP\{
    Transport\Protocol\Reader\Exchange,
    Transport\Protocol\Methods,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Exception\UnknownMethod,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Str,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class ExchangeTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInvokation($method, $arguments)
    {
        $read = new Exchange;

        $stream = $read(
            Methods::get($method),
            Stream::ofContent(\implode('', $arguments)),
        );

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertCount(\count($arguments), $stream);

        foreach ($arguments as $i => $argument) {
            $this->assertInstanceOf(\get_class($argument), $stream->get($i)->match(
                static fn($value) => $value,
                static fn() => null,
            ));
            $this->assertSame((string) $argument, $stream->get($i)->match(
                static fn($value) => (string) $value,
                static fn() => null,
            ));
        }
    }

    public function testThrowWhenUnknownMethod()
    {
        $this->expectException(UnknownMethod::class);
        $this->expectExceptionMessage('0,0');

        (new Exchange)(new Method(0, 0), Stream::ofContent(''));
    }

    public function cases(): array
    {
        return [
            [
                'exchange.declare-ok',
                [],
            ],
            [
                'exchange.delete-ok',
                [],
            ],
        ];
    }
}
