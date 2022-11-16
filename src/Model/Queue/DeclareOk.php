<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Count;

/**
 * @psalm-immutable
 */
final class DeclareOk
{
    private string $name;
    private Count $message;
    private Count $consumer;

    private function __construct(string $name, Count $message, Count $consumer)
    {
        $this->name = $name;
        $this->message = $message;
        $this->consumer = $consumer;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $name, Count $message, Count $consumer): self
    {
        return new self($name, $message, $consumer);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function message(): Count
    {
        return $this->message;
    }

    public function consumer(): Count
    {
        return $this->consumer;
    }
}
