<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

/**
 * The AMQP user that published the message
 *
 * @psalm-immutable
 */
final class UserId
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
