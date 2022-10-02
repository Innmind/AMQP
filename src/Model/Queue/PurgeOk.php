<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Count;

/**
 * @psalm-immutable
 */
final class PurgeOk
{
    private Count $message;

    public function __construct(Count $message)
    {
        $this->message = $message;
    }

    public function message(): Count
    {
        return $this->message;
    }
}
