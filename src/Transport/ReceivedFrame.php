<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

/**
 * @internal
 */
final class ReceivedFrame
{
    private Frame $frame;

    private function __construct(Frame $frame)
    {
        $this->frame = $frame;
    }

    public static function of(Frame $frame): self
    {
        return new self($frame);
    }

    public function frame(): Frame
    {
        return $this->frame;
    }

    public function is(Frame\Method $method): bool
    {
        return $this->frame->is($method);
    }

    public function oneOf(Frame\Method ...$methods): bool
    {
        foreach ($methods as $method) {
            if ($this->frame->is($method)) {
                return true;
            }
        }

        return false;
    }
}
