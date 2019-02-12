<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Frame\Method;

final class UnknownMethod extends LogicException
{
    public function __construct(Method $method)
    {
        parent::__construct(\sprintf(
            '%s,%s',
            $method->class(),
            $method->method()
        ));
    }
}
