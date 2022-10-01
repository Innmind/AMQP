<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\ArgumentTranslator;

use Innmind\AMQP\{
    Transport\Protocol\ArgumentTranslator\Delegate,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame\Value,
    Exception\ValueNotTranslatable,
};
use PHPUnit\Framework\TestCase;

class DelegateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(ArgumentTranslator::class, new Delegate);
    }

    public function testInvokation()
    {
        $translate = new Delegate(
            $first = $this->createMock(ArgumentTranslator::class),
            $second = $this->createMock(ArgumentTranslator::class),
            $third = $this->createMock(ArgumentTranslator::class),
        );
        $value = 'foo';
        $first
            ->expects($this->once())
            ->method('__invoke')
            ->with($value)
            ->will($this->throwException(new ValueNotTranslatable($value)));
        $second
            ->expects($this->once())
            ->method('__invoke')
            ->with($value)
            ->willReturn($expected = $this->createMock(Value::class));
        $third
            ->expects($this->never())
            ->method('__invoke');

        $this->assertSame($expected, $translate($value));
    }

    public function testThrowWhenValueNotTranslatable()
    {
        $translate = new Delegate(
            $inner = $this->createMock(ArgumentTranslator::class),
        );
        $value = 'foo';
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($value)
            ->will(
                $exception = $this->throwException(new ValueNotTranslatable($value)),
            );

        try {
            $translate($value);
            $this->fail('it should throw an exception');
        } catch (ValueNotTranslatable $e) {
            //verify it's the delegate that throws its own exception and not the inner
            $this->assertNotSame($exception, $e);
            $this->assertSame($value, $e->value());
        }
    }
}
