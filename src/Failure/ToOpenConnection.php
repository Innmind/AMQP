<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class ToOpenConnection extends Failure
{
    /**
     * @internal
     */
    public function __construct()
    {
    }

    public function kind(): Kind
    {
        return Kind::toOpenConnection;
    }
}
