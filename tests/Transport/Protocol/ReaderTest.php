<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\{
    Protocol\Reader,
    Frame\Method,
    Frame\Value,
    Frame\Value\ShortString,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\UnsignedLongLongInteger,
    Frame\Value\Bits,
    Frame\Value\UnsignedLongInteger,
    Frame\Value\UnsignedOctet,
    Frame\Value\Table,
    Frame\Value\LongString
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInvokation($method, $arguments)
    {
        $read = new Reader;

        $args = '';

        foreach ($arguments as $arg) {
            $args .= $arg->pack();
        }

        $stream = $read(
            $method,
            Stream::ofContent($args),
        );

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertCount(\count($arguments), $stream);

        foreach ($arguments as $i => $argument) {
            $this->assertInstanceOf(\get_class($argument), $stream->get($i)->match(
                static fn($value) => $value,
                static fn() => null,
            ));
            $this->assertSame($argument->pack(), $stream->get($i)->match(
                static fn($value) => $value->pack(),
                static fn() => null,
            ));
        }
    }

    public function cases(): array
    {
        return [
            [
                Method::basicQosOk,
                [],
            ],
            [
                Method::basicConsumeOk,
                [new ShortString(Str::of('foo'))],
            ],
            [
                Method::basicCancelOk,
                [new ShortString(Str::of('foo'))],
            ],
            [
                Method::basicReturn,
                [
                    new UnsignedShortInteger(Integer::of(42)),
                    new ShortString(Str::of('foo')),
                    new ShortString(Str::of('bar')),
                    new ShortString(Str::of('baz')),
                ],
            ],
            [
                Method::basicDeliver,
                [
                    new ShortString(Str::of('foo')),
                    new UnsignedLongLongInteger(Integer::of(42)),
                    new Bits(true),
                    new ShortString(Str::of('bar')),
                    new ShortString(Str::of('baz')),
                ],
            ],
            [
                Method::basicGetOk,
                [
                    new UnsignedLongLongInteger(Integer::of(42)),
                    new Bits(true),
                    new ShortString(Str::of('foo')),
                    new ShortString(Str::of('bar')),
                    new UnsignedLongInteger(Integer::of(24)),
                ],
            ],
            [
                Method::basicGetEmpty,
                [],
            ],
            [
                Method::basicRecoverOk,
                [],
            ],
            [
                Method::channelOpenOk,
                [],
            ],
            [
                Method::channelFlow,
                [new Bits(true)],
            ],
            [
                Method::channelFlowOk,
                [new Bits(true)],
            ],
            [
                Method::channelClose,
                [
                    new UnsignedShortInteger(Integer::of(42)),
                    new ShortString(Str::of('foo')),
                    new UnsignedShortInteger(Integer::of(24)),
                    new UnsignedShortInteger(Integer::of(66)),
                ],
            ],
            [
                Method::channelCloseOk,
                [],
            ],
            [
                Method::connectionStart,
                [
                    new UnsignedOctet(Integer::of(0)),
                    new UnsignedOctet(Integer::of(9)),
                    new Table(Map::of()),
                    new LongString(Str::of('foo')),
                    new LongString(Str::of('bar')),
                ],
            ],
            [
                Method::connectionSecure,
                [new LongString(Str::of('foo'))],
            ],
            [
                Method::connectionTune,
                [
                    new UnsignedShortInteger(Integer::of(1)),
                    new UnsignedLongInteger(Integer::of(2)),
                    new UnsignedShortInteger(Integer::of(3)),
                ],
            ],
            [
                Method::connectionOpenOk,
                [
                    new ShortString(Str::of('foo')),
                ],
            ],
            [
                Method::connectionClose,
                [
                    new UnsignedShortInteger(Integer::of(0)),
                    new ShortString(Str::of('foo')),
                    new UnsignedShortInteger(Integer::of(1)),
                    new UnsignedShortInteger(Integer::of(2)),
                ],
            ],
            [
                Method::connectionCloseOk,
                [],
            ],
            [
                Method::exchangeDeclareOk,
                [],
            ],
            [
                Method::exchangeDeleteOk,
                [],
            ],
            [
                Method::queueDeclareOk,
                [
                    new ShortString(Str::of('foo')),
                    new UnsignedLongInteger(Integer::of(42)),
                    new UnsignedLongInteger(Integer::of(24)),
                ],
            ],
            [
                Method::queueBindOk,
                [],
            ],
            [
                Method::queueUnbindOk,
                [],
            ],
            [
                Method::queuePurgeOk,
                [new UnsignedLongInteger(Integer::of(42))],
            ],
            [
                Method::queueDeleteOk,
                [new UnsignedLongInteger(Integer::of(42))],
            ],
            [
                Method::transactionSelectOk,
                [],
            ],
            [
                Method::transactionCommitOk,
                [],
            ],
            [
                Method::transactionRollbackOk,
                [],
            ],
        ];
    }
}
