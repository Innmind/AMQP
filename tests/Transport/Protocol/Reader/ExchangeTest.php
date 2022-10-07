<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\Reader;

use Innmind\AMQP\{
    Transport\Protocol\Reader\Exchange,
    Transport\Frame\Method,
    Transport\Frame\Value,
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
            Method::of($method),
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
