<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Model\Connection\MaxFrameSize;

final class FrameExceedAllowedSize extends LogicException
{
    public function __construct(int $size, MaxFrameSize $max)
    {
        parent::__construct(sprintf(
            'Max frame size can be %s but got %s',
            $max->toInt(),
            $size
        ));
    }
}
