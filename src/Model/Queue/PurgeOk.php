<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Count;

final class PurgeOk
{
    private $message;

    public function __construct(Count $message)
    {
        $this->message = $message;
    }

    public function message(): Count
    {
        return $this->message;
    }
}
