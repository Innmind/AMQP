<?php
declare(strict_types = 1);

namespace Innmind\AMQP\TimeContinuum\Format;

use Innmind\TimeContinuum\Format;

/**
 * @psalm-immutable
 * @internal
 */
final class Timestamp implements Format
{
    #[\Override]
    public function toString(): string
    {
        return 'U';
    }
}
