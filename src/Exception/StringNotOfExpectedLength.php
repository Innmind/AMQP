<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\Immutable\Str;

final class StringNotOfExpectedLength extends DomainException
{
    public function __construct(Str $string, int $length)
    {
        parent::__construct(sprintf(
            'String "%s" is expected of being %s characters, got %s',
            $string,
            $length,
            $string->length()
        ));
    }
}
