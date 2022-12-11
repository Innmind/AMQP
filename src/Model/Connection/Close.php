<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Close
{
    /** @var Maybe<array{int<0, 65535>, string}> */
    private Maybe $reply;

    private function __construct()
    {
        /** @var Maybe<array{int<0, 65535>, string}> */
        $this->reply = Maybe::nothing();
    }

    /**
     * @psalm-pure
     */
    public static function demand(): self
    {
        return new self;
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 65535> $code
     */
    public static function reply(int $code, string $text): self
    {
        $self = new self;
        $self->reply = Maybe::just([$code, $text]);

        return $self;
    }

    /**
     * @return Maybe<array{int<0, 65535>, string}>
     */
    public function response(): Maybe
    {
        return $this->reply;
    }
}
