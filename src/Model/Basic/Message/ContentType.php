<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Exception\DomainException;
use Innmind\MediaType\{
    MediaType,
    Exception\Exception,
};

/**
 * Same behaviour as HTTP Content-Type header
 *
 * @psalm-immutable
 */
final class ContentType
{
    private string $value;

    public function __construct(string $topLevel, string $subType)
    {
        try {
            new MediaType($topLevel, $subType);
            $this->value = $topLevel.'/'.$subType;
        } catch (Exception $e) {
            throw new DomainException($topLevel.'/'.$subType);
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}
