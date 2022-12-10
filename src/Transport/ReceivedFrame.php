<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

/**
 * @internal
 */
final class ReceivedFrame
{
    private Connection $connection;
    private Frame $frame;

    private function __construct(Connection $connection, Frame $frame)
    {
        $this->connection = $connection;
        $this->frame = $frame;
    }

    public static function of(Connection $connection, Frame $frame): self
    {
        return new self($connection, $frame);
    }

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function frame(): Frame
    {
        return $this->frame;
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
