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
final class ShortString implements Value
{
    private Str $original;

    private function __construct(Str $string)
    {
        $this->original = $string;
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): self
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $_ = UnsignedOctet::of($string->toEncoding('ASCII')->length());

        return new self($string);
    }

    public static function unpack(Readable $stream): self
    {
        $length = UnsignedOctet::unpack($stream)->original();
        /** @psalm-suppress InvalidArgument */
        $string = $stream
            ->read($length)
            ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
            ->filter(static fn($chunk) => $chunk->length() === $length)
            ->match(
                static fn($string) => $string,
                static fn() => throw new \LogicException,
            );

        return new self($string);
    }

    public function original(): Str
    {
        return $this->original;
    }

    public function pack(): string
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return UnsignedOctet::of(
            $this->original->toEncoding('ASCII')->length(),
        )->pack().$this->original->toString();
    }
}
