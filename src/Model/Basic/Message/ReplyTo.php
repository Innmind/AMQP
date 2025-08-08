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
    private function __construct(private string $value)
    {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(string $value): self
    {
        return new self($value);
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->value;
    }
}
