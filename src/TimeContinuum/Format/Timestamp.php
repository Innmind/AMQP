<?php
declare(strict_types = 1);

namespace Innmind\AMQP\TimeContinuum\Format;

use Innmind\TimeContinuum\Format;

/**
 * @internal
 */
final class Timestamp
{
    /**
     * @psalm-pure
     */
    public static function new(): Format
    {
        return Format::of('U');
    }
}
