<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\Frame\Value;

interface ArgumentTranslator
{
    /**
     * @throws ArgumentNotTranslatable
     */
    public function __invoke($value): Value;
}
