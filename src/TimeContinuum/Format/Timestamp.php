<?php
declare(strict_types = 1);

namespace Innmind\AMQP\TimeContinuum\Format;

use Innmind\TimeContinuum\FormatInterface;

final class Timestamp implements FormatInterface
{
    public function __toString(): string
    {
        return 'U';
    }
}
