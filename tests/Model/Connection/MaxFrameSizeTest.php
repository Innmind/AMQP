<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\{
    Model\Connection\MaxFrameSize,
    Exception\FrameExceedAllowedSize,
};
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use PHPUnit\Framework\Attributes\Group;

class MaxFrameSizeTest extends TestCase
{
    use BlackBox;

    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $max = MaxFrameSize::of(42);

        $this->assertSame(42, $max->toInt());
        $this->assertTrue($max->allows(0));
        $this->assertTrue($max->allows(42));
        $this->assertFalse($max->allows(43));

        $this->assertTrue((MaxFrameSize::of(0))->allows(1));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testAllowAnySizeWhenNoLimit(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::integers()->between(0, 4294967295)) // max allowed by the specification 0.9.1
            ->prove(function($size) {
                $max = MaxFrameSize::of(0);

                $this->assertTrue($max->allows($size));
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testDoesntAllowAnySizeAboveTheLimit(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::integers()->between(9, 4294967295), // max allowed by the specification 0.9.1
                Set::integers()->between(1, 4294967295 - 9),
            )
            ->prove(function($allowed, $extraSize) {
                $max = MaxFrameSize::of($allowed);

                $this->assertFalse($max->allows($allowed + $extraSize));
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testAllowAnySizeBelowTheLimit(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::integers()->between(9, 4294967295), // max allowed by the specification 0.9.1
                Set::integers()->between(0, 4294967295 - 9),
            )
            ->prove(function($allowed, $sizeBelow) {
                $max = MaxFrameSize::of($allowed);

                $this->assertTrue($max->allows($allowed - $sizeBelow));
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testVerifyAllowedSizes()
    {
        $this
            ->forAll(Set::integers()->between(0, 4294967295)) // max allowed by the specification 0.9.1
            ->then(function($size) {
                $max = MaxFrameSize::of(0);

                $this->assertInstanceOf(
                    SideEffect::class,
                    $max->verify($size)->unwrap(),
                );
            });
        $this
            ->forAll(
                Set::integers()->between(9, 4294967295), // max allowed by the specification 0.9.1
                Set::integers()->between(0, 4294967295 - 9),
            )
            ->then(function($allowed, $sizeBelow) {
                $max = MaxFrameSize::of($allowed);

                $this->assertInstanceOf(
                    SideEffect::class,
                    $max->verify($allowed - $sizeBelow)->unwrap(),
                );
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenVerifyingSizeAboveMaxAllowed(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::integers()->between(9, 4294967295), // max allowed by the specification 0.9.1
                Set::integers()->between(1, 4294967295 - 9),
            )
            ->prove(function($allowed, $extraSize) {
                $max = MaxFrameSize::of($allowed);

                $above = $allowed + $extraSize;

                $this->expectException(FrameExceedAllowedSize::class);
                $this->expectExceptionMessage("Max frame size can be $allowed but got $above");

                $max->verify($above)->unwrap();
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testIsLimited()
    {
        $this->assertTrue(MaxFrameSize::of(42)->isLimited());
        $this->assertTrue(MaxFrameSize::of(9)->isLimited());
        $this->assertFalse(MaxFrameSize::of(0)->isLimited());
    }
}
