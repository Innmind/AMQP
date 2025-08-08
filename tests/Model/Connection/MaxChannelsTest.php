<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\{
    Model\Connection\MaxChannels,
    Exception\FrameChannelExceedAllowedChannelNumber,
};
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use PHPUnit\Framework\Attributes\Group;

class MaxChannelsTest extends TestCase
{
    use BlackBox;

    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $max = MaxChannels::of(42);

        $this->assertSame(42, $max->toInt());
        $this->assertTrue($max->allows(0));
        $this->assertTrue($max->allows(42));
        $this->assertFalse($max->allows(43));

        $this->assertTrue((MaxChannels::of(0))->allows(1));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testAllowAnyNumberWhenNoLimit(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::integers()->between(0, 65535)) // max allowed by the specification 0.9.1
            ->prove(function($number) {
                $max = MaxChannels::of(0);

                $this->assertTrue($max->allows($number));
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testDoesntAllowAnyNumberAboveTheLimit(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::integers()->between(1, 65535), // max allowed by the specification 0.9.1
                Set::integers()->between(1, 65535),
            )
            ->prove(function($allowed, $extraNumber) {
                $max = MaxChannels::of($allowed);

                $this->assertFalse($max->allows($allowed + $extraNumber));
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testAllowAnyNumberBelowTheLimit(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::integers()->between(1, 65535), // max allowed by the specification 0.9.1
                Set::integers()->between(0, 65535),
            )
            ->prove(function($allowed, $sizeBelow) {
                $max = MaxChannels::of($allowed);

                $this->assertTrue($max->allows($allowed - $sizeBelow));
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testVerifyAllowedNumbers()
    {
        $this
            ->forAll(Set::integers()->between(0, 65535)) // max allowed by the specification 0.9.1
            ->then(function($size) {
                $max = MaxChannels::of(0);

                $this->assertInstanceOf(
                    SideEffect::class,
                    $max->verify($size)->unwrap(),
                );
            });
        $this
            ->forAll(
                Set::integers()->between(0, 65535), // max allowed by the specification 0.9.1
                Set::integers()->between(1, 65535),
            )
            ->then(function($allowed, $numberBelow) {
                $max = MaxChannels::of($allowed);

                $this->assertInstanceOf(
                    SideEffect::class,
                    $max->verify($allowed - $numberBelow)->unwrap(),
                );
            });
    }

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenVerifyingNumberAboveMaxAllowed(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::integers()->between(1, 65535), // max allowed by the specification 0.9.1, minimum of 1 as 0 means unlimited
                Set::integers()->between(1, 65535),
            )
            ->prove(function($allowed, $extraNumber) {
                $max = MaxChannels::of($allowed);

                $above = $allowed + $extraNumber;

                try {
                    $max->verify($above)->unwrap();
                    $this->fail('it should throw');
                } catch (FrameChannelExceedAllowedChannelNumber $e) {
                    $this->assertSame(
                        "Max channel id can be $allowed but got $above",
                        $e->getMessage(),
                    );
                }
            });
    }
}
