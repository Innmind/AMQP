<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Count;

/**
 * @psalm-immutable
 */
final class DeleteOk
{
    private Count $message;

    private function __construct(Count $message)
    {
        $this->message = $message;
    }

    /**
     * @psalm-pure
     */
    public static function of(Count $message): self
    {
        return new self($message);
    }

    public function message(): Count
    {
        return $this->message;
    }
}
