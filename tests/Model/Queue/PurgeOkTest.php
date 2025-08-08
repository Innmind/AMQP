<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\{
    Queue\PurgeOk,
    Count,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PurgeOkTest extends TestCase
{
    public function testInterface()
    {
        $model = PurgeOk::of(
            $message = Count::of(1),
        );

        $this->assertSame($message, $model->message());
    }
}
