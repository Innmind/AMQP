<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\ValueNotTranslatable,
};

interface ArgumentTranslator
{
    /**
     * @throws ValueNotTranslatable
     */
    public function __invoke(mixed $value): Value;
}
