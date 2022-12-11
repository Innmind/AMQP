<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class ToCloseChannel extends Failure
{
    /**
     * @internal
     */
    public function __construct()
    {
    }

    public function kind(): Kind
    {
        return Kind::toCloseChannel;
    }
}
