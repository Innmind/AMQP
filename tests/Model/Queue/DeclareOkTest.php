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
        $model = DeclareOk::of(
            'foo',
            $message = Count::of(1),
            $consumer = Count::of(2),
        );

        $this->assertSame('foo', $model->name());
        $this->assertSame($message, $model->message());
        $this->assertSame($consumer, $model->consumer());
    }
}
