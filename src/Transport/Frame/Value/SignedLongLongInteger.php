<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;

/**
 * @implements Value<Integer>
 * @psalm-immutable
 */
final class SignedLongLongInteger implements Value
{
    private Integer $original;

    public function __construct(Integer $value)
    {
        $this->original = $value;
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream
            ->read(8)
            ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
            ->filter(static fn($chunk) => $chunk->length() === 8)
            ->match(
                static fn($chunk) => $chunk,
                static fn() => throw new \LogicException,
            );
        /** @var int $value */
        [, $value] = \unpack('q', $chunk->toString());

        return new self(Integer::of($value));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('q', $this->original->value());
    }
}
