<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\ValueNotTranslatable,
};

final class ArgumentTranslator
{
    public function __invoke(mixed $value): Value
    {
        if ($value instanceof Value) {
            return $value;
        }

        // TODO find a way to support decimals
        return Value\Bits::wrap($value)
            ->otherwise(Value\ShortString::wrap(...))
            ->otherwise(Value\LongString::wrap(...))
            ->otherwise(Value\UnsignedOctet::wrap(...))
            ->otherwise(Value\UnsignedShortInteger::wrap(...))
            ->otherwise(Value\UnsignedLongInteger::wrap(...))
            ->otherwise(Value\UnsignedLongLongInteger::wrap(...))
            ->otherwise(Value\SignedOctet::wrap(...))
            ->otherwise(Value\SignedShortInteger::wrap(...))
            ->otherwise(Value\SignedLongInteger::wrap(...))
            ->otherwise(Value\SignedLongLongInteger::wrap(...))
            ->otherwise(Value\Timestamp::wrap(...))
            ->otherwise(fn($value) => Value\Sequence::wrap($this, $value))
            ->otherwise(fn($value) => Value\Table::wrap($this, $value))
            ->match(
                static fn($value) => $value,
                static fn($value) => throw new ValueNotTranslatable($value),
            );
    }
}
