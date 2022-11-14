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
use Innmind\TimeContinuum\Earth\Clock;
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
        $read = new Reader(new Clock);

        $args = '';

        foreach ($arguments as $arg) {
            $args .= $arg->pack()->toString();
        }

        $stream = $read(
            $method,
            Stream::ofContent($args),
        )->match(
            static fn($values) => $values,
            static fn() => null,
        );

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertCount(\count($arguments), $stream);

        foreach ($arguments as $i => $argument) {
            $this->assertInstanceOf(\get_class($argument), $stream->get($i)->match(
                static fn($value) => $value,
                static fn() => null,
            ));
            $this->assertSame(
                $argument->pack()->toString(),
                $stream->get($i)->match(
                    static fn($value) => $value->pack()->toString(),
                    static fn() => null,
                ),
            );
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
                [ShortString::literal('foo')],
            ],
            [
                Method::basicCancelOk,
                [ShortString::literal('foo')],
            ],
            [
                Method::basicReturn,
                [
                    UnsignedShortInteger::of(42),
                    ShortString::literal('foo'),
                    ShortString::literal('bar'),
                    ShortString::literal('baz'),
                ],
            ],
            [
                Method::basicDeliver,
                [
                    ShortString::literal('foo'),
                    UnsignedLongLongInteger::of(42),
                    Bits::of(true),
                    ShortString::literal('bar'),
                    ShortString::literal('baz'),
                ],
            ],
            [
                Method::basicGetOk,
                [
                    UnsignedLongLongInteger::of(42),
                    Bits::of(true),
                    ShortString::literal('foo'),
                    ShortString::literal('bar'),
                    UnsignedLongInteger::of(24),
                ],
            ],
            [
                Method::basicGetEmpty,
                [
                    ShortString::literal('foo'),
                ],
            ],
            [
                Method::basicRecoverOk,
                [],
            ],
            [
                Method::channelOpenOk,
                [
                    LongString::literal('foo'),
                ],
            ],
            [
                Method::channelFlow,
                [Bits::of(true)],
            ],
            [
                Method::channelFlowOk,
                [Bits::of(true)],
            ],
            [
                Method::channelClose,
                [
                    UnsignedShortInteger::of(42),
                    ShortString::literal('foo'),
                    UnsignedShortInteger::of(24),
                    UnsignedShortInteger::of(66),
                ],
            ],
            [
                Method::channelCloseOk,
                [],
            ],
            [
                Method::connectionStart,
                [
                    UnsignedOctet::of(0),
                    UnsignedOctet::of(9),
                    Table::of(Map::of()),
                    LongString::of(Str::of('foo')),
                    LongString::of(Str::of('bar')),
                ],
            ],
            [
                Method::connectionSecure,
                [LongString::of(Str::of('foo'))],
            ],
            [
                Method::connectionTune,
                [
                    UnsignedShortInteger::of(1),
                    UnsignedLongInteger::of(2),
                    UnsignedShortInteger::of(3),
                ],
            ],
            [
                Method::connectionOpenOk,
                [
                    ShortString::literal('foo'),
                ],
            ],
            [
                Method::connectionClose,
                [
                    UnsignedShortInteger::of(0),
                    ShortString::literal('foo'),
                    UnsignedShortInteger::of(1),
                    UnsignedShortInteger::of(2),
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
                    ShortString::literal('foo'),
                    UnsignedLongInteger::of(42),
                    UnsignedLongInteger::of(24),
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
                [UnsignedLongInteger::of(42)],
            ],
            [
                Method::queueDeleteOk,
                [UnsignedLongInteger::of(42)],
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
