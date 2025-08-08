<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\{
    Model\Basic\Message,
    Exception\FrameExceedAllowedSize,
};
use Innmind\Immutable\{
    Sequence,
    Str,
    Attempt,
    SideEffect,
};

/**
 * @psalm-immutable
 */
final class MaxFrameSize
{
    /** @var int<0, 4294967295> */
    private int $value;

    /**
     * @param int<0, 4294967295> $value
     */
    private function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 4294967295> $value
     */
    #[\NoDiscard]
    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function unlimited(): self
    {
        return new self(0);
    }

    /**
     * @psalm-assert-if-true positive-int $this->value
     * @psalm-assert-if-true positive-int $this->toInt()
     */
    #[\NoDiscard]
    public function isLimited(): bool
    {
        return $this->value > 0;
    }

    #[\NoDiscard]
    public function allows(int $size): bool
    {
        if (!$this->isLimited()) {
            return true;
        }

        return $size <= $this->value;
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function verify(int $size): Attempt
    {
        if (!$this->allows($size)) {
            return Attempt::error(new FrameExceedAllowedSize($size, $this));
        }

        return Attempt::result(SideEffect::identity());
    }

    /**
     * @return int<0, 4294967295>
     */
    #[\NoDiscard]
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * @return Sequence<Str>
     */
    #[\NoDiscard]
    public function chunk(Message $message): Sequence
    {
        if (!$this->isLimited()) {
            return $message->chunks();
        }

        /** @psalm-suppress InvalidArgument Psalm forgets the condition above when in the callable below */
        return $message
            ->chunks()
            ->flatMap(fn($chunk) => $chunk->chunk($this->value));
    }
}
