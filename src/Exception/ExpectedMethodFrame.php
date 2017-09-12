<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Frame\Type;

final class ExpectedMethodFrame extends RuntimeException
{
    public function __construct(Type $type)
    {
        parent::__construct(sprintf(
            'Expected method but got %s',
            $type === Type::header() ? 'header' : 'body'
        ));
    }
}
