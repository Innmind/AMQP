<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

/**
 * @implements Value<Str>
 * @psalm-immutable
 */
final class Text implements Value
{
    private string $value;
    private Str $original;

    public function __construct(Str $string)
    {
        $this->value = $string->toString();
        $this->original = $string;
    }

    public static function unpack(Readable $stream): self
    {
        $text = $stream->read()->match(
            static fn($text) => $text,
            static fn() => throw new \LogicException,
        );

        return new self($text);
    }

    public function original(): Str
    {
        return $this->original;
    }

    public function pack(): string
    {
        return $this->value;
    }
}
