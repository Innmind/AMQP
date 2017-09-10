<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Frame\Method;

final class ConnectionClosed extends RuntimeException
{
    private $cause;

    public function __construct(string $message, int $code, Method $cause)
    {
        parent::__construct($message, $code);
        $this->cause = $cause;
    }

    public function cause(): Method
    {
        return $this->cause;
    }
}
