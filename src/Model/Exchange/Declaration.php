<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Exchange;

use Innmind\AMQP\Exception\NotWaitingPassiveDeclarationDoesNothing;
use Innmind\Immutable\Map;

final class Declaration
{
    private string $name;
    private Type $type;
    private bool $passive = false;
    private bool $durable = false;
    private bool $autoDelete = false;
    private bool $wait = true;
    private Map $arguments;

    private function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
        $this->arguments = Map::of('string', 'mixed');
    }

    /**
     * Check if the exchange with the given name exists on the server
     */
    public static function passive(string $name, Type $type): self
    {
        $self = new self($name, $type);
        $self->passive = true;

        return $self;
    }

    /**
     * The exchange will survive after a server restart
     */
    public static function durable(string $name, Type $type): self
    {
        $self = new self($name, $type);
        $self->durable = true;

        return $self;
    }

    /**
     * The exchange will disappear after a server restart
     */
    public static function temporary(string $name, Type $type): self
    {
        return new self($name, $type);
    }

    /**
     * The exchange will disappear once it's no longer used
     */
    public static function autoDelete(string $name, Type $type): self
    {
        $self = new self($name, $type);
        $self->autoDelete = true;
        $self->durable = false;

        return $self;
    }

    /**
     * Don't wait for the server to respond
     */
    public function dontWait(): self
    {
        if ($this->isPassive()) {
            throw new NotWaitingPassiveDeclarationDoesNothing;
        }

        $self = clone $this;
        $self->wait = false;

        return $self;
    }

    /**
     * Wait for the server to respond
     */
    public function wait(): self
    {
        $self = clone $this;
        $self->wait = true;

        return $self;
    }

    public function withArgument(string $key, $value): self
    {
        $self = clone $this;
        $self->arguments = $self->arguments->put($key, $value);

        return $self;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function isPassive(): bool
    {
        return $this->passive;
    }

    public function isDurable(): bool
    {
        return $this->durable;
    }

    public function isAutoDeleted(): bool
    {
        return $this->autoDelete;
    }

    public function shouldWait(): bool
    {
        return $this->wait;
    }

    /**
     * @return Map<string, mixed>
     */
    public function arguments(): Map
    {
        return $this->arguments;
    }
}
