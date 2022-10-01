<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\{
    Queue\DeclareOk,
    Count,
};
use PHPUnit\Framework\TestCase;

class DeclareOkTest extends TestCase
{
    public function testInterface()
    {
        $model = new DeclareOk(
            'foo',
            $message = new Count(1),
            $consumer = new Count(2),
        );

        $this->assertSame('foo', $model->name());
        $this->assertSame($message, $model->message());
        $this->assertSame($consumer, $model->consumer());
    }
}
