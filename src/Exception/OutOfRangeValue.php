<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
};

final class OutOfRangeValue extends DomainException
{
    public function __construct(Integer $value, Set $set)
    {
        parent::__construct(\sprintf('%s ∉ %s', $value, $set));
    }
}
