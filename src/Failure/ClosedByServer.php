<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\{
    Failure,
    Transport\Frame\Method,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class ClosedByServer extends Failure
{
    private string $message;
    /** @var int<0, 65535> */
    private int $code;
    /** @var Maybe<Method> */
    private Maybe $method;

    /**
     * @internal
     *
     * @param int<0, 65535> $code
     * @param Maybe<Method> $method
     */
    public function __construct(string $message, int $code, Maybe $method)
    {
        $this->message = $message;
        $this->code = $code;
        $this->method = $method;
    }

    public function kind(): Kind
    {
        return Kind::closedByServer;
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
