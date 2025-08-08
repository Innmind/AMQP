<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Transport\Frame\Method;
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class ClosedByServer
{
    /**
     * @internal
     *
     * @param int<0, 65535> $code
     * @param Maybe<Method> $method
     */
    public function __construct(
        private string $message,
        private int $code,
        private Maybe $method,
    ) {
    }

    #[\NoDiscard]
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return int<0, 65535>
     */
    #[\NoDiscard]
    public function code(): int
    {
        return $this->code;
    }

    /**
     * @return Maybe<Method>
     */
    #[\NoDiscard]
    public function method(): Maybe
    {
        return $this->method;
    }
}
