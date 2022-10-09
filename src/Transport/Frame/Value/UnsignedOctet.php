<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\Range,
};
use Innmind\Stream\Readable;

/**
 * Same as unsigned shortshort
 *
 * @implements Value<int<0, 255>>
 * @psalm-immutable
 */
final class UnsignedOctet implements Value
{
    /** @var int<0, 255> */
    private int $original;

    /**
     * @param int<0, 255> $octet
     */
    private function __construct(int $octet)
    {
        $this->original = $octet;
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param int<0, 255> $octet
     */
    public static function internal(int $octet): self
    {
        return new self($octet);
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 255> $octet
     */
    public static function of(int $octet): self
    {
        self::definitionSet()->accept(Integer::of($octet));

        return new self($octet);
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream
            ->read(1)
            ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
            ->filter(static fn($chunk) => $chunk->length() === 1)
            ->match(
                static fn($chunk) => $chunk,
                static fn() => throw new \LogicException,
            );

        /** @var int<0, 255> $octet */
        [, $octet] = \unpack('C', $chunk->toString());

        return new self($octet);
    }

    /**
     * @return int<0, 255>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \chr($this->original);
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(0),
            Integer::of(255),
        );
    }
}
