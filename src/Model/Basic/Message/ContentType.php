<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Exception\DomainException;
use Innmind\MediaType\{
    MediaType,
    Exception\Exception,
};
use Innmind\Immutable\Maybe;

/**
 * Same behaviour as HTTP Content-Type header
 *
 * @psalm-immutable
 */
final class ContentType
{
    private string $value;

    private function __construct(MediaType $type)
    {
        $this->value = $type->topLevel().'/'.$type->subType();
    }

    /**
     * @psalm-pure
     *
     * @param literal-string $topLevel
     * @param literal-string $subType
     *
     * @throws DomainException
     */
    #[\NoDiscard]
    public static function of(string $topLevel, string $subType): self
    {
        try {
            return new self(new MediaType($topLevel, $subType));
        } catch (Exception $e) {
            throw new DomainException("$topLevel/$subType");
        }
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function maybe(string $value): Maybe
    {
        return MediaType::maybe($value)->map(static fn($type) => new self($type));
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->value;
    }
}
