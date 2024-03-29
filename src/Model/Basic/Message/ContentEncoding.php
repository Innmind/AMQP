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
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @throws DomainException
     */
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
    public static function maybe(string $value): Maybe
    {
        return Maybe::just($value)
            ->map(Str::of(...))
            ->filter(static fn($value) => $value->matches('~^[\w\-]+$~'))
            ->map(static fn($value) => new self($value->toString()));
    }

    public function toString(): string
    {
        return $this->value;
    }
}
