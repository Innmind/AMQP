<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Frame\Method;

final class ConnectionClosed extends RuntimeException
{
    private $cause;

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

    public function cause(): Method
    {
        return $this->cause;
    }
}
