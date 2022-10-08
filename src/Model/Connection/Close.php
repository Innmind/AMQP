<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Close
{
    /** @var Maybe<array{int, string}> */
    private Maybe $reply;

    public function __construct()
    {
        /** @var Maybe<array{int, string}> */
        $this->reply = Maybe::nothing();
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
     * @return Maybe<array{int, string}>
     */
    public function response(): Maybe
    {
        return $this->reply;
    }
}
