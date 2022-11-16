<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\{
    Model\Connection\MaxFrameSize,
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
        $max = MaxFrameSize::of(42);

        $this->assertSame(42, $max->toInt());
        $this->assertTrue($max->allows(0));
        $this->assertTrue($max->allows(42));
        $this->assertFalse($max->allows(43));

        $this->assertTrue((MaxFrameSize::of(0))->allows(1));
    }

    public function testAllowAnySizeWhenNoLimit()
    {
        $this
            ->forAll(Set\Integers::between(0, 4294967295)) // max allowed by the specification 0.9.1
            ->then(function($size) {
                $max = MaxFrameSize::of(0);

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
                $max = MaxFrameSize::of($allowed);

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
                $max = MaxFrameSize::of($allowed);

                $this->assertTrue($max->allows($allowed - $sizeBelow));
            });
    }

    public function testVerifyAllowedSizes()
    {
        $this
            ->forAll(Set\Integers::between(0, 4294967295)) // max allowed by the specification 0.9.1
            ->then(function($size) {
                $max = MaxFrameSize::of(0);

                $this->assertNull($max->verify($size));
            });
        $this
            ->forAll(
                Set\Integers::between(9, 4294967295), // max allowed by the specification 0.9.1
                Set\Integers::between(0, 4294967295 - 9),
            )
            ->then(function($allowed, $sizeBelow) {
                $max = MaxFrameSize::of($allowed);

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
                $max = MaxFrameSize::of($allowed);

                $above = $allowed + $extraSize;

                $this->expectException(FrameExceedAllowedSize::class);
                $this->expectExceptionMessage("Max frame size can be $allowed but got $above");

                $max->verify($above);
            });
    }

    public function testIsLimited()
    {
        $this->assertTrue(MaxFrameSize::of(42)->isLimited());
        $this->assertTrue(MaxFrameSize::of(9)->isLimited());
        $this->assertFalse(MaxFrameSize::of(0)->isLimited());
    }
}
