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
    #[\NoDiscard]
    public static function of(string $name, Count $message, Count $consumer): self
    {
        return new self($name, $message, $consumer);
    }

    #[\NoDiscard]
    public function name(): string
    {
        return $this->name;
    }

    #[\NoDiscard]
    public function message(): Count
    {
        return $this->message;
    }

    #[\NoDiscard]
    public function consumer(): Count
    {
        return $this->consumer;
    }
}
