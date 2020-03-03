<?php
declare(strict_types = 1);

namespace Innmind\AMQP\TimeContinuum\Format;

use Innmind\TimeContinuum\Format;

final class Timestamp implements Format
{
    public function toString(): string
    {
        return 'U';
    }
}
