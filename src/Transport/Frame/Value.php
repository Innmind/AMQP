<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\Immutable\Str;

/**
 * @template T
 *
 * @psalm-immutable
 */
interface Value
{
    /**
     * Original value
     *
     * @return T
     */
    public function original();
    public function symbol(): Value\Symbol;
    public function pack(): Str;
}
