<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;

/**
 * @implements Value<int>
 * @psalm-immutable
 */
final class SignedLongLongInteger implements Value
{
    private int $original;

    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     */
    public static function of(int $value): self
    {
        return new self($value);
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

        return new self($value);
    }

    public function original(): int
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('q', $this->original);
    }
}
