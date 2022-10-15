<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

final class ValueNotTranslatable extends LogicException
{
    private mixed $value;

    public function __construct(mixed $value)
    {
        parent::__construct();
        $this->value = $value;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}
