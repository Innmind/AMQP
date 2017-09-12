<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\AMQP\Exception\DomainException;

final class Channel
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
