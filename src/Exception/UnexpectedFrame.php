<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Frame\Method;

final class UnexpectedFrame extends RuntimeException
{
    public function __construct(Method $method, string ...$names)
    {
        parent::__construct(sprintf(
            'Expected %s but got %s.%s',
            implode(' or ', $names),
            $method->class(),
            $method->method()
        ));
    }
}
