<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\{
    Queue\DeleteOk,
    Count,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class DeleteOkTest extends TestCase
{
    public function testInterface()
    {
        $model = DeleteOk::of(
            $message = Count::of(1),
        );

        $this->assertSame($message, $model->message());
    }
}
