<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

use Innmind\Immutable\{
    MapInterface,
    Str
};

interface Message
{
    /**
     * @return MapInterface<string, mixed>
     */
    public function properties(): MapInterface;
    public function body(): Str;
}
