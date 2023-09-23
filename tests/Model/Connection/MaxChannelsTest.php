<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\{
    Model\Connection\MaxChannels,
    Exception\FrameChannelExceedAllowedChannelNumber,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class MaxChannelsTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $max = MaxChannels::of(42);

        $this->assertSame(42, $max->toInt());
        $this->assertTrue($max->allows(0));
        $this->assertTrue($max->allows(42));
        $this->assertFalse($max->allows(43));

        $this->assertTrue((MaxChannels::of(0))->allows(1));
    }

    public function testAllowAnyNumberWhenNoLimit()
    {
        $this
            ->forAll(Set\Integers::between(0, 65535)) // max allowed by the specification 0.9.1
            ->then(function($number) {
                $max = MaxChannels::of(0);

                $this->assertTrue($max->allows($number));
            });
    }

    public function testDoesntAllowAnyNumberAboveTheLimit()
    {
        $this
            ->forAll(
                Set\Integers::between(1, 65535), // max allowed by the specification 0.9.1
                Set\Integers::between(1, 65535),
            )
            ->then(function($allowed, $extraNumber) {
                $max = MaxChannels::of($allowed);

                $this->assertFalse($max->allows($allowed + $extraNumber));
            });
    }

    public function testAllowAnyNumberBelowTheLimit()
    {
        $this
            ->forAll(
                Set\Integers::between(1, 65535), // max allowed by the specification 0.9.1
                Set\Integers::between(0, 65535),
            )
            ->then(function($allowed, $sizeBelow) {
                $max = MaxChannels::of($allowed);

                $this->assertTrue($max->allows($allowed - $sizeBelow));
            });
    }

    public function testVerifyAllowedNumbers()
    {
        $this
            ->forAll(Set\Integers::between(0, 65535)) // max allowed by the specification 0.9.1
            ->then(function($size) {
                $max = MaxChannels::of(0);

                $this->assertNull($max->verify($size));
            });
        $this
            ->forAll(
                Set\Integers::between(0, 65535), // max allowed by the specification 0.9.1
                Set\Integers::between(1, 65535),
            )
            ->then(function($allowed, $numberBelow) {
                $max = MaxChannels::of($allowed);

                $this->assertNull($max->verify($allowed - $numberBelow));
            });
    }

    public function testThrowWhenVerifyingNumberAboveMaxAllowed()
    {
        $this
            ->forAll(
                Set\Integers::between(0, 65535), // max allowed by the specification 0.9.1
                Set\Integers::between(1, 65535),
            )
            ->then(function($allowed, $extraNumber) {
                $max = MaxChannels::of($allowed);

                $above = $allowed + $extraNumber;

                try {
                    $max->verify($above);
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
