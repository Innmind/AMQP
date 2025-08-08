<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame\Value,
    Exception\ValueNotTranslatable,
};
use Innmind\Immutable\{
    Sequence,
    Map,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

class ArgumentTranslatorTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(ArgumentTranslator::class, new ArgumentTranslator);
    }

    public function testInvokation()
    {
        $value = new Value\VoidValue;

        $this->assertSame($value, (new ArgumentTranslator)($value));
    }

    public function testWideRangeOfValues()
    {
        $primitive = Set\Either::any(
            Set\Integers::any(),
            PointInTime::any(),
        );

        $this
            ->forAll($primitive)
            ->then(function($value) {
                $this->assertInstanceOf(
                    Value::class,
                    (new ArgumentTranslator)($value),
                );
                $this->assertSame(
                    $value,
                    (new ArgumentTranslator)($value)->original(),
                );
            });
        $this
            ->forAll(Set\Unicode::strings())
            ->then(function($value) {
                $this->assertInstanceOf(
                    Value::class,
                    (new ArgumentTranslator)($value),
                );
                $this->assertSame(
                    $value,
                    (new ArgumentTranslator)($value)->original()->toString(),
                );
            });
        $this
            ->forAll(Set\Sequence::of($primitive)->map(static fn($values) => Sequence::of(...$values)))
            ->then(function($value) {
                $this->assertInstanceOf(
                    Value::class,
                    (new ArgumentTranslator)($value),
                );
                $this->assertSame(
                    $value->toList(),
                    (new ArgumentTranslator)($value)
                        ->original()
                        ->map(static fn($value) => $value->original())
                        ->toList(),
                );
            });
        $this
            ->forAll(
                Set\Sequence::of(
                    Set\Strings::madeOf(Set\Chars::alphanumerical())
                        ->atMost(255),
                )->atMost(20),
                Set\Sequence::of($primitive)->atMost(20),
            )
            ->then(function($keys, $values) {
                $max = \min(\count($keys), \count($values));
                $value = Map::of();

                for ($i = 0; $i < $max; ++$i) {
                    $value = ($value)($keys[$i], $values[$i]);
                }

                $this->assertInstanceOf(
                    Value::class,
                    (new ArgumentTranslator)($value),
                );
                $this->assertTrue(
                    $value->equals(
                        (new ArgumentTranslator)($value)
                            ->original()
                            ->map(static fn($_, $value) => $value->original()),
                    ),
                );
            });
    }

    public function testThrowWhenValueNotTranslatable()
    {
        try {
            $value = new \stdClass;
            (new ArgumentTranslator)($value);
            $this->fail('it should throw an exception');
        } catch (ValueNotTranslatable $e) {
            $this->assertSame($value, $e->value());
        }
    }
}
