<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Readable\{
    Stream,
    Frame,
};
use Innmind\Socket\Client;
use Innmind\Immutable\{
    Str,
    Maybe,
};

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
     *
     * @param literal-string $string Of maximum 255 length
     */
    public static function literal(string $string): self
    {
        return new self(Str::of($string));
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): self
    {
        /** @psalm-suppress InvalidArgument */
        $_ = UnsignedOctet::of($string->toEncoding(Str\Encoding::ascii)->length());

        return new self($string);
    }

    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<self>
     */
    public static function unpack(Stream $stream): Maybe
    {
        /** @psalm-suppress InvalidArgument */
        return UnsignedOctet::unpack($stream)
            ->map(static fn($length) => $length->original())
            ->flatMap(
                static fn($length) => $stream
                    ->frames(Frame\Chunk::of($length))
                    ->one()
                    ->filter(static fn($string) => $string->length() === $length),
            )
            ->map(static fn($string) => new self($string));
    }

    public function original(): Str
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::shortString;
    }

    public function pack(): Str
    {
        /** @psalm-suppress InvalidArgument */
        return UnsignedOctet::of(
            $this->original->toEncoding(Str\Encoding::ascii)->length(),
        )
            ->pack()
            ->append($this->original->toString());
    }
}
