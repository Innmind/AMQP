<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Exception\DomainException;
use Innmind\Immutable\Str;

/**
 * Same behaviour as HTTP Content-Encoding header
 */
final class ContentEncoding
{
    private string $value;

    public function __construct(string $value)
    {
        if (!Str::of($value)->matches('~^[\w\-]+$~')) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
