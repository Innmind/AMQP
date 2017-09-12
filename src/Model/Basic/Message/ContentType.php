<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Exception\DomainException;
use Innmind\Filesystem\{
    MediaType\MediaType,
    Exception\InvalidArgumentException
};

/**
 * Same behaviour as HTTP Content-Type header
 */
final class ContentType
{
    private $value;

    public function __construct(string $topLevel, string $subType)
    {
        try {
            $mediaType = new MediaType($topLevel, $subType);
            $this->value = $topLevel.'/'.$subType;
        } catch (InvalidArgumentException $e) {
            throw new DomainException;
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
