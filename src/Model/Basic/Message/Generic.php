<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message;
use Innmind\Immutable\{
    MapInterface,
    Str
};

final class Generic implements Message
{
    private $properties;
    private $body;

    public function __construct(MapInterface $properties, Str $body)
    {
        if (
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, mixed>');
        }

        $this->properties = $properties;
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function properties(): MapInterface
    {
        return $this->properties;
    }

    public function body(): Str
    {
        return $this->body;
    }
}
