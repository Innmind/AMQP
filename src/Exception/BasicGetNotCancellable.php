<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

final class BasicGetNotCancellable extends LogicException
{
    public function __construct()
    {
        parent::__construct('This happened if you called Continuation::cancel inside the handler of Command\\Get');
    }
}
