<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class ToReadFrame extends Failure
{
    public function kind(): Kind
    {
        return Kind::toReadFrame;
    }
}
