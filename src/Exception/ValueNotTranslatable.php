<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

final class ValueNotTranslatable extends LogicException
{
    /** @var mixed */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        parent::__construct();
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }
}
