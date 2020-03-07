<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Exception\{
    ExclusivePassiveDeclarationNotAllowed,
    NotWaitingPassiveDeclarationDoesNothing,
    PassiveQueueDeclarationMustHaveAName,
};
use Innmind\Immutable\Map;

final class Declaration
{
    private ?string $name = null;
    private bool $passive = false;
    private bool $durable = false;
    private bool $autoDelete = false;
    private bool $exclusive = false;
    private bool $wait = true;
    /** @var Map<string, mixed> */
    private Map $arguments;

    private function __construct()
    {
        /** @var Map<string, mixed> */
        $this->arguments = Map::of('string', 'mixed');
    }

    /**
     * Check if the queue exists on the server
     */
    public static function passive(string $name): self
    {
        $self = new self;
        $self->passive = true;

        return $self->withName($name);
    }

    /**
     * The queue will survive after a server restart
     */
    public static function durable(): self
    {
        $self = new self;
        $self->durable = true;

        return $self;
    }

    /**
     * The queue will disappear after a server restart
     */
    public static function temporary(): self
    {
        return new self;
    }

    /**
     * The queue is deleted once all consumers have finished using it
     */
    public static function autoDelete(): self
    {
        $self = new self;
        $self->autoDelete = true;

        return $self;
    }

    /**
     * Make the queue only accessible to the current connection
     */
    public function exclusive(): self
    {
        if ($this->isPassive()) {
            throw new ExclusivePassiveDeclarationNotAllowed;
        }

        $self = clone $this;
        $self->exclusive = true;

        return $self;
    }

    /**
     * Make the queue usable to all connections
     */
    public function notExclusive(): self
    {
        $self = clone $this;
        $self->exclusive = false;

        return $self;
    }

    /**
     * Don't wait for the server response
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
     * Wait for the server response
     */
    public function wait(): self
    {
        $self = clone $this;
        $self->wait = true;

        return $self;
    }

    /**
     * Name the queue with the given string
     */
    public function withName(string $name): self
    {
        $self = clone $this;
        $self->name = $name;

        return $self;
    }

    /**
     * Let the server generate a name for the queue
     */
    public function withAutoGeneratedName(): self
    {
        if ($this->isPassive()) {
            throw new PassiveQueueDeclarationMustHaveAName;
        }

        $self = clone $this;
        $self->name = null;

        return $self;
    }

    /**
     * @param mixed $value
     */
    public function withArgument(string $key, $value): self
    {
        $self = clone $this;
        $self->arguments = ($self->arguments)($key, $value);

        return $self;
    }

    public function shouldAutoGenerateName(): bool
    {
        return !\is_string($this->name);
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function name(): string
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->name;
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

    public function isExclusive(): bool
    {
        return $this->exclusive;
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
