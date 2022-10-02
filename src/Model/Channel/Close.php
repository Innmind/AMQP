<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Channel;

use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Close
{
    /** @var Maybe<array{int, string}> */
    private Maybe $reply;
    /** @var Maybe<string> */
    private Maybe $cause;

    public function __construct()
    {
        /** @var Maybe<array{int, string}> */
        $this->reply = Maybe::nothing();
        /** @var Maybe<string> */
        $this->cause = Maybe::nothing();
    }

    /**
     * @psalm-pure
     */
    public static function reply(int $code, string $text): self
    {
        $self = new self;
        $self->reply = Maybe::just([$code, $text]);

        return $self;
    }

    /**
     * @param string $method ie: exchange.declare, channel.open, etc
     */
    public function causedBy(string $method): self
    {
        $self = clone $this;
        $self->cause = Maybe::just($method);

        return $self;
    }

    /**
     * @return Maybe<array{int, string}>
     */
    public function response(): Maybe
    {
        return $this->reply;
    }

    /**
     * @return Maybe<string>
     */
    public function cause(): Maybe
    {
        return $this->cause;
    }
}
