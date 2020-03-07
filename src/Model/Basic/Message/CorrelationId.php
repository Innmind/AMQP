<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

/**
 * Id of the message that this message is a reply to
 */
final class CorrelationId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
