<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

interface Value
{
    public function __toString(): string;
}
