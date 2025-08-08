<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Exception\DomainException;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * Same behaviour as HTTP Content-Encoding header
 *
 * @psalm-immutable
 */
final class ContentEncoding
{
    private function __construct(private string $value)
    {
    }

    /**
     * @psalm-pure
     *
     * @throws DomainException
     */
    #[\NoDiscard]
    public static function of(string $value): self
    {
        return self::maybe($value)->match(
            static fn($self) => $self,
            static fn() => throw new DomainException($value),
        );
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function maybe(string $value): Maybe
    {
        return Maybe::just($value)
            ->map(Str::of(...))
            ->filter(static fn($value) => $value->matches('~^[\w\-]+$~'))
            ->map(static fn($value) => new self($value->toString()));
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->value;
    }
}
