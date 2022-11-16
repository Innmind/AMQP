<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Predicate;

use Innmind\Immutable\Predicate;

/**
 * @implements Predicate<int<0, max>>
 * @psalm-immutable
 */
final class IsInt implements Predicate
{
    private function __construct()
    {
    }

    public function __invoke(mixed $value): bool
    {
        return \is_int($value) && $value >= 0;
    }

    public static function natural(): self
    {
        return new self;
    }
}
