<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Count;

/**
 * @psalm-immutable
 */
final class DeleteOk
{
    private function __construct(private Count $message)
    {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(Count $message): self
    {
        return new self($message);
    }

    #[\NoDiscard]
    public function message(): Count
    {
        return $this->message;
    }
}
