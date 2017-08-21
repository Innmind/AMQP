<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

final class Close
{
    private $replyCode;
    private $replyText;
    private $cause;

    public static function reply(int $code, string $text): self
    {
        $self = new self;
        $self->replyCode = $code;
        $self->replyText = $text;

        return $self;
    }

    /**
     * @param string $method ie: exchange.declare, channel.open, etc
     */
    public function causedBy(string $method): self
    {
        $self = clone $this;
        $self->cause = $method;

        return $self;
    }

    public function hasReply(): bool
    {
        return is_int($this->replyCode);
    }

    public function replyCode(): int
    {
        return $this->replyCode;
    }

    public function replyText(): string
    {
        return $this->replyText;
    }

    public function causedKnown(): bool
    {
        return is_string($this->cause);
    }

    public function cause(): string
    {
        return $this->cause;
    }
}
