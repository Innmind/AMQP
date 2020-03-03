<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Protocol\Delegate,
    Transport\Protocol,
    Transport\Frame\Method,
    Transport\Protocol\Version,
    Transport\Protocol\Connection,
    Transport\Protocol\Channel,
    Transport\Protocol\Exchange,
    Transport\Protocol\Queue,
    Transport\Protocol\Basic,
    Transport\Protocol\Transaction,
    Exception\VersionNotUsable,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class DelegateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Protocol::class,
            new Delegate($this->createMock(Protocol::class))
        );
    }

    public function testSelectHighestProtocol()
    {
        $first = $this->protocol(new Version(0, 8, 0));
        $second = $this->protocol(new Version(0, 9, 0));
        $third = $this->protocol($expected = new Version(1, 0, 0));
        $protocol = new Delegate($first, $second, $third);

        $this->assertSame($expected, $protocol->version());
    }

    public function testUse()
    {
        $first = $this->protocol(new Version(0, 9, 1));
        $second = $this->protocol($expected = new Version(0, 9, 5));
        $third = $this->protocol(new Version(1, 0, 0));
        $protocol = new Delegate($first, $second, $third);

        $this->assertSame($protocol, $protocol->use(new Version(0, 9, 0)));
        $this->assertSame($expected, $protocol->version());
    }

    private function protocol(Version $version): Protocol
    {
        return new class($version) implements Protocol {
            private $version;

            public function __construct(Version $version)
            {
                $this->version = $version;
            }

            public function version(): Version
            {
                return $this->version;
            }

            public function use(Version $version): Protocol
            {
                if (!$version->compatibleWith($this->version)) {
                    throw new VersionNotUsable($version);
                }

                return $this;
            }

            public function read(Method $method, Readable $arguments): Sequence {}
            public function readHeader(Readable $arguments): Sequence {}
            public function method(string $name): Method {}
            public function connection(): Connection {}
            public function channel(): Channel {}
            public function exchange(): Exchange {}
            public function queue(): Queue {}
            public function basic(): Basic {}
            public function transaction(): Transaction {}
        };
    }
}
