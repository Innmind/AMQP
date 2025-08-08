<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\IO\Frame;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
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
     * @psalm-pure
     *
     * @return Either<mixed, Value>
     */
    public static function wrap(mixed $value): Either
    {
        return Maybe::of($value)
            ->filter(\is_string(...))
            ->map(Str::of(...))
            ->map(static fn($str) => $str->toEncoding(Str\Encoding::ascii))
            ->flatMap(
                static fn($str) => UnsignedOctet::wrap($str->length())
                    ->maybe()
                    ->map(static fn() => new self($str)),
            )
            ->either()
            ->leftMap(static fn(): mixed => $value);
    }

    /**
     * @psalm-pure
     *
     * @return Frame<Unpacked<self>>
     */
    public static function frame(): Frame
    {
        return UnsignedOctet::frame()->flatMap(
            static fn($length) => (match ($length->unwrap()->original()) {
                0 => Frame::just(Str::of('')),
                default => Frame::chunk($length->unwrap()->original())->strict(),
            })
                ->map(static fn($string) => new self($string))
                ->map(static fn($value) => Unpacked::of(
                    $length->read() + $length->unwrap()->original(),
                    $value,
                )),
        );
    }

    #[\Override]
    public function original(): Str
    {
        return $this->original;
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::shortString;
    }

    #[\Override]
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
