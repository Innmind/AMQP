<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\{
    Model\Connection\MaxFrameSize,
    Exception\DomainException,
    Exception\FrameExceedAllowedSize,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class MaxFrameSizeTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $max = new MaxFrameSize(42);

        $this->assertSame(42, $max->toInt());
        $this->assertTrue($max->allows(0));
        $this->assertTrue($max->allows(42));
        $this->assertFalse($max->allows(43));

        $this->assertTrue((new MaxFrameSize(0))->allows(1));
    }

    public function testAllowAnySizeWhenNoLimit()
    {
        $this
            ->forAll(Set\Integers::between(0, 4294967295)) // max allowed by the specification 0.9.1
            ->then(function($size) {
                $max = new MaxFrameSize(0);

                $this->assertTrue($max->allows($size));
            });
    }

    public function testDoesntAllowAnySizeAboveTheLimit()
    {
        $this
            ->forAll(
                Set\Integers::between(9, 4294967295), // max allowed by the specification 0.9.1
                Set\Integers::between(1, 4294967295 - 9),
            )
            ->then(function($allowed, $extraSize) {
                $max = new MaxFrameSize($allowed);

                $this->assertFalse($max->allows($allowed + $extraSize));
            });
    }

    public function testAllowAnySizeBelowTheLimit()
    {
        $this
            ->forAll(
                Set\Integers::between(9, 4294967295), // max allowed by the specification 0.9.1
                Set\Integers::between(0, 4294967295 - 9),
            )
            ->then(function($allowed, $sizeBelow) {
                $max = new MaxFrameSize($allowed);

                $this->assertTrue($max->allows($allowed - $sizeBelow));
            });
    }

    public function testVerifyAllowedSizes()
    {
        $this
            ->forAll(Set\Integers::between(0, 4294967295)) // max allowed by the specification 0.9.1
            ->then(function($size) {
                $max = new MaxFrameSize(0);

                $this->assertNull($max->verify($size));
            });
        $this
            ->forAll(
                Set\Integers::between(9, 4294967295), // max allowed by the specification 0.9.1
                Set\Integers::between(0, 4294967295 - 9),
            )
            ->then(function($allowed, $sizeBelow) {
                $max = new MaxFrameSize($allowed);

                $this->assertNull($max->verify($allowed - $sizeBelow));
            });
    }

    public function testThrowWhenVerifyingSizeAboveMaxAllowed()
    {
        $this
            ->forAll(
                Set\Integers::between(9, 4294967295), // max allowed by the specification 0.9.1
                Set\Integers::between(1, 4294967295 - 9),
            )
            ->then(function($allowed, $extraSize) {
                $max = new MaxFrameSize($allowed);

                $above = $allowed + $extraSize;

                $this->expectException(FrameExceedAllowedSize::class);
                $this->expectExceptionMessage("Max frame size can be $allowed but got $above");

                $max->verify($above);
            });
    }

    public function testIsLimited()
    {
        $this->assertTrue((new MaxFrameSize(42))->isLimited());
        $this->assertTrue((new MaxFrameSize(9))->isLimited());
        $this->assertFalse((new MaxFrameSize(0))->isLimited());
    }

    public function testThrowWhenNegativeValue()
    {
        $this->expectException(DomainException::class);

        new MaxFrameSize(-1);
    }

    /**
     * @dataProvider invalid
     */
    public function testThrowWhenValueLowerThanFrameFlags($int)
    {
        $this->expectException(DomainException::class);

        //meaning that channel id + payload size int + frame end flag
        //already make a size of 8 leaving no place for the payload
        new MaxFrameSize($int);
    }

    public function invalid(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
        ];
    }
}
