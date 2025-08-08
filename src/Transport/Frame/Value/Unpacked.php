<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;

/**
 * @internal
 * @psalm-immutable
 * @template T of Value
 */
final class Unpacked
{
    /**
     * @param 0|positive-int $read
     * @param T $value
     */
    private function __construct(
        private int $read,
        private Value $value,
    ) {
    }

    /**
     * @psalm-pure
     * @template V of Value
     *
     * @param 0|positive-int $read
     * @param V $value
     *
     * @return self<V>
     */
    public static function of(int $read, Value $value): self
    {
        return new self($read, $value);
    }

    /**
     * @return 0|positive-int
     */
    public function read(): int
    {
        return $this->read;
    }

    /**
     * @return T
     */
    public function unwrap(): Value
    {
        return $this->value;
    }
}
