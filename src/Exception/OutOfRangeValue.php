<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

final class OutOfRangeValue extends DomainException
{
    public function __construct(int $value, int $lowerBound, int $upperBound)
    {
        parent::__construct(sprintf(
            'Expected value between %s and %s, got %s',
            $lowerBound,
            $upperBound,
            $value
        ));
    }
}
