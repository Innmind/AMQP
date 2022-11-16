<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\{
    Model\Basic\Message,
    Exception\DomainException,
    Exception\FrameExceedAllowedSize,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class MaxFrameSize
{
    /** @var 0|int<9, 4294967295> */
    private int $value;

    /**
     * @param 0|int<9, 4294967295> $value
     */
    private function __construct(int $value)
    {
        if ($value !== 0 && $value < 9) {
            throw new DomainException((string) $value);
        }

        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param 0|int<9, 4294967295> $value
     */
    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     */
    public static function unlimited(): self
    {
        return new self(0);
    }

    /**
     * @psalm-assert-if-false 0 $this->value
     * @psalm-assert-if-false 0 $this->toInt()
     */
    public function isLimited(): bool
    {
        return $this->value > 0;
    }

    public function allows(int $size): bool
    {
        if (!$this->isLimited()) {
            return true;
        }

        return $size <= $this->value;
    }

    /**
     * @throws FrameExceedAllowedSize
     */
    public function verify(int $size): void
    {
        if (!$this->allows($size)) {
            throw new FrameExceedAllowedSize($size, $this);
        }
    }

    /**
     * @return 0|int<9, 4294967295>
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * @return Sequence<Str>
     */
    public function chunk(Message $message): Sequence
    {
        if (!$this->isLimited()) {
            return Sequence::of($message->body());
        }

        /**
         * the "-8" is due to the content frame extra informations (type,
         * channel and end flag)
         * @psalm-suppress InvalidArgument
         */
        return $message->body()->chunk($this->value - 8);
    }
}
