<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Channel;

final class Close
{
    private ?int $replyCode = null;
    private ?string $replyText = null;
    private ?string $cause = null;

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
        return \is_int($this->replyCode);
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function replyCode(): int
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->replyCode;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function replyText(): string
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->replyText;
    }

    public function causedKnown(): bool
    {
        return \is_string($this->cause);
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function cause(): string
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->cause;
    }
}
