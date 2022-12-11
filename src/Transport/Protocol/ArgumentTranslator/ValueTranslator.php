<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\ArgumentTranslator;

use Innmind\AMQP\{
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame\Value,
    Exception\ValueNotTranslatable,
};

final class ValueTranslator implements ArgumentTranslator
{
    public function __invoke(mixed $value): Value
    {
        if (!$value instanceof Value) {
            throw new ValueNotTranslatable($value);
        }

        return $value;
    }
}
