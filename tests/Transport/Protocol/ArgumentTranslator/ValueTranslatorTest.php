<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\ArgumentTranslator;

use Innmind\AMQP\{
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame\Value,
    Exception\ValueNotTranslatable,
};
use PHPUnit\Framework\TestCase;

class ValueTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(ArgumentTranslator::class, new ValueTranslator);
    }

    public function testInvokation()
    {
        $value = $this->createMock(Value::class);

        $this->assertSame($value, (new ValueTranslator)($value));
    }

    public function testThrowWhenValueNotTranslatable()
    {
        try {
            $value = new \stdClass;
            (new ValueTranslator)($value);
            $this->fail('it should throw an exception');
        } catch (ValueNotTranslatable $e) {
            $this->assertSame($value, $e->value());
        }
    }
}
