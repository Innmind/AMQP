<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\AMQP\Exception\UnknownFrameType;

final class Type
{
    private const METHOD = 1;
    private const HEADER = 2;
    private const BODY = 3;
    private const HEARTBEAT = 8;

    private static ?self $method = null;
    private static ?self $header = null;
    private static ?self $body = null;
    private static ?self $heartbeat = null;

    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function method(): self
    {
        return self::$method ?? self::$method = new self(self::METHOD);
    }

    public static function header(): self
    {
        return self::$header ?? self::$header = new self(self::HEADER);
    }

    public static function body(): self
    {
        return self::$body ?? self::$body = new self(self::BODY);
    }

    public static function heartbeat(): self
    {
        return self::$heartbeat ?? self::$heartbeat = new self(self::HEARTBEAT);
    }

    public static function fromInt(int $value): self
    {
        switch ($value) {
            case self::METHOD:
                return self::method();

            case self::HEADER:
                return self::header();

            case self::BODY:
                return self::body();

            case self::HEARTBEAT:
                return self::heartbeat();

            default:
                throw new UnknownFrameType((string) $value);
        }
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
