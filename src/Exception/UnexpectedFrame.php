<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Frame;

final class UnexpectedFrame extends RuntimeException
{
    private Frame $frame;

    public function __construct(Frame $frame, string ...$names)
    {
        parent::__construct(sprintf(
            'Expected %s',
            \implode(' or ', $names)
        ));
        $this->frame = $frame;
    }

    public function frame(): Frame
    {
        return $this->frame;
    }
}
