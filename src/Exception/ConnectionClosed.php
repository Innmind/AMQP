<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Frame\Method;

final class ConnectionClosed extends RuntimeException
{
    private ?Method $cause = null;

    public static function byServer(string $message, int $code, Method $cause): self
    {
        $self = new self($message, $code);
        $self->cause = $cause;

        return $self;
    }

    public function hasCause(): bool
    {
        return $this->cause instanceof Method && !$this->cause->equals(new Method(0, 0));
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function cause(): Method
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->cause;
    }
}
