<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\Id;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class IdTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertSame('foo', Id::of('foo')->toString());
    }
}
