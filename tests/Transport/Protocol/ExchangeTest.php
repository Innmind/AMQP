<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Protocol\Exchange,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\Table,
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Model\Exchange\Type,
};
use PHPUnit\Framework\TestCase;

class ExchangeTest extends TestCase
{
    private $exchange;
    private $translator;

    public function setUp(): void
    {
        $this->exchange = new Exchange(
            $this->translator = $this->createMock(ArgumentTranslator::class),
        );
    }

    public function testDeclare()
    {
        $this
            ->translator
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive([24], [42])
            ->will($this->onConsecutiveCalls(
                $firstArgument = UnsignedShortInteger::of(24),
                $secondArgument = UnsignedShortInteger::of(42),
            ));
        $frame = $this->exchange->declare(
            $channel = new Channel(1),
            Declaration::passive('foo', Type::direct)
                ->withArgument('foo', 24)
                ->withArgument('bar', 42),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Frame\Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(40, 10)));
        $this->assertCount(5, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(0, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('foo', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('direct', $frame->values()->get(2)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(3)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(
            [true, false, false, false, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(Table::class, $frame->values()->get(4)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertCount(2, $frame->values()->get(4)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertSame($firstArgument, $frame->values()->get(4)->match(
            static fn($value) => $value->original()->get('foo')->match(
                static fn($argument) => $argument,
                static fn() => null,
            ),
            static fn() => null,
        ));
        $this->assertSame($secondArgument, $frame->values()->get(4)->match(
            static fn($value) => $value->original()->get('bar')->match(
                static fn($argument) => $argument,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->exchange->declare(
            $channel = new Channel(1),
            Declaration::durable('foo', Type::direct),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [false, true, false, false, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->exchange->declare(
            $channel = new Channel(1),
            Declaration::temporary('foo', Type::direct),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [false, false, false, false, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->exchange->declare(
            $channel = new Channel(1),
            Declaration::autoDelete('foo', Type::direct),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [false, false, true, false, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->exchange->declare(
            $channel = new Channel(1),
            Declaration::autoDelete('foo', Type::direct)->dontWait(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [false, false, true, false, true],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
    }

    public function testDeletion()
    {
        $frame = $this->exchange->delete(
            $channel = new Channel(1),
            Deletion::of('foo'),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Frame\Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(40, 20)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(0, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('foo', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(
            [false, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->exchange->delete(
            $channel = new Channel(1),
            Deletion::of('foo')->ifUnused(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [true, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->exchange->delete(
            $channel = new Channel(1),
            Deletion::of('foo')->dontWait(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [false, true],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
    }
}
