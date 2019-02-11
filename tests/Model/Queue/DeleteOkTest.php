<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\{
    Queue\DeleteOk,
    Count,
};
use PHPUnit\Framework\TestCase;

class DeleteOkTest extends TestCase
{
    public function testInterface()
    {
        $model = new DeleteOk(
            $message = new Count(1)
        );

        $this->assertSame($message, $model->message());
    }
}
