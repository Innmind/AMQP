<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

/**
 * Commonly used to name a reply queue, but can be any information in order to
 * direct the response of the message
 *
 * @psalm-immutable
 */
final class ReplyTo
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
