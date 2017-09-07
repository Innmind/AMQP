<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

final class ValueNotTranslatable extends LogicException
{
    private $value;

    public function __construct($value)
    {
        parent::__construct();
        $this->value = $value;
    }

    public function value()
    {
        return $this->value;
    }
}
