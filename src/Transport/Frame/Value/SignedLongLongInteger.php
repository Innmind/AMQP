<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;

final class SignedLongLongInteger implements Value
{
    private ?string $value = null;
    private Integer $original;

    public function __construct(Integer $value)
    {
        $this->original = $value;
    }

    public static function fromStream(Readable $stream): Value
    {
        [, $value] = \unpack('q', $stream->read(8)->toString());

        return new self(new Integer($value));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value ?? $this->value = \pack('q', $this->original->value());
    }
}
