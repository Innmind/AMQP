<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Frame\Method;
use Innmind\Immutable\Maybe;

final class ConnectionClosed extends RuntimeException
{
    /** @var Maybe<Method> */
    private Maybe $cause;

    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
        /** @var Maybe<Method> */
        $this->cause = Maybe::nothing();
    }

    /**
     * @param Maybe<Method> $cause
     */
    public static function byServer(string $message, int $code, Maybe $cause): self
    {
        $self = new self($message, $code);
        $self->cause = $cause;

        return $self;
    }

    /**
     * @return Maybe<Method>
     */
    public function cause(): Maybe
    {
        return $this->cause;
    }
}
