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

    public function __construct(string $topLevel, string $subType)
    {
        try {
            new MediaType($topLevel, $subType);
            $this->value = $topLevel.'/'.$subType;
        } catch (Exception $e) {
            throw new DomainException($topLevel.'/'.$subType);
        }
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function maybe(string $value): Maybe
    {
        return MediaType::maybe($value)->map(static fn($type) => new self(
            $type->topLevel(),
            $type->subType(),
        ));
    }

    public function toString(): string
    {
        return $this->value;
    }
}
