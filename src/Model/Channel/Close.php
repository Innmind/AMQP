<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Channel;

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
    #[\NoDiscard]
    public static function demand(): self
    {
        return new self;
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 65535> $code
     */
    #[\NoDiscard]
    public static function reply(int $code, string $text): self
    {
        $self = new self;
        $self->reply = Maybe::just([$code, $text]);

        return $self;
    }

    /**
     * @return Maybe<array{int<0, 65535>, string}>
     */
    #[\NoDiscard]
    public function response(): Maybe
    {
        return $this->reply;
    }
}
