<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;

/**
 * @implements Value<Integer>
 */
final class SignedLongLongInteger implements Value
{
    private Integer $original;

    public function __construct(Integer $value)
    {
        $this->original = $value;
    }

    public static function unpack(Readable $stream): self
    {
        /** @var int $value */
        [, $value] = \unpack('q', $stream->read(8)->toString());

        return new self(new Integer($value));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('q', $this->original->value());
    }
}
