<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\Transport\{
    Protocol,
    Frame,
    Frame\Method,
    Frame\MethodClass,
    Frame\Type,
    Frame\Channel,
    Frame\Value\UnsignedOctet,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\UnsignedLongInteger,
};
use Innmind\IO\Readable\{
    Stream,
    Frame as IOFrame,
};
use Innmind\Socket\Client;
use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @internal
 */
final class FrameReader
{
    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<Frame>
     */
    public function __invoke(Stream $stream, Protocol $protocol): Maybe
    {
        $frame = $this->readType()->flatMap(
            fn($type) => $this
                ->readChannel()
                ->flatMap(fn($channel) => $this->readFrame(
                    $type,
                    $channel,
                    $protocol,
                )),
        );

        return $stream
            ->frames($frame)
            ->one();
    }

    /**
     * @return IOFrame<Frame>
     */
    private function readFrame(
        Type $type,
        Channel $channel,
        Protocol $protocol,
    ): IOFrame {
        return UnsignedLongInteger::frame()
            ->map(static fn($value) => $value->unwrap()->original())
            ->flatMap(fn($length) => match ($type) {
                Type::method => $this->readMethod($protocol, $channel),
                Type::header => $this->readHeader($protocol, $channel),
                Type::body => $this->readBody($channel, $length),
                Type::heartbeat => IOFrame\NoOp::of(Frame::heartbeat()),
            })
            ->flatMap(
                static fn($frame) => UnsignedOctet::frame()
                    ->map(static fn($end) => $end->unwrap()->original())
                    ->filter(static fn($end) => $end === Frame::end())
                    ->map(static fn() => $frame),
            );
    }

    /**
     * @return IOFrame<Type>
     */
    private function readType(): IOFrame
    {
        return UnsignedOctet::frame()
            ->map(static fn($octet) => $octet->unwrap()->original())
            ->flatMap(Type::frame(...));
    }

    /**
     * @return IOFrame<Channel>
     */
    private function readChannel(): IOFrame
    {
        return UnsignedShortInteger::frame()
            ->map(static fn($value) => $value->unwrap()->original())
            ->map(static fn($value) => new Channel($value));
    }

    /**
     * @return IOFrame<Frame>
     */
    private function readMethod(
        Protocol $protocol,
        Channel $channel,
    ): IOFrame {
        return UnsignedShortInteger::frame()
            ->map(static fn($value) => $value->unwrap()->original())
            ->flatMap(
                static fn($class) => UnsignedShortInteger::frame()
                    ->map(static fn($value) => $value->unwrap()->original())
                    ->flatMap(static fn($method) => Method::frame($class, $method)),
            )
            ->flatMap(
                static fn($method) => $protocol
                    ->frame($method)
                    ->map(static fn($values) => Frame::method(
                        $channel,
                        $method,
                        ...$values->toList(),
                    )),
            );
    }

    /**
     * @return IOFrame<Frame>
     */
    private function readHeader(
        Protocol $protocol,
        Channel $channel,
    ): IOFrame {
        return UnsignedShortInteger::frame()
            ->map(static fn($value) => $value->unwrap()->original())
            ->flatMap(MethodClass::frame(...))
            ->flatMap(
                static fn($class) => IOFrame\Chunk::of(2) // walk over the weight definition
                    ->map(static fn() => $class),
            )
            ->flatMap(
                static fn($class) => $protocol
                    ->headerFrame()
                    ->map(static fn($arguments) => Frame::header(
                        $channel,
                        $class,
                        ...$arguments->toList(),
                    )),
            );
    }

    /**
     * @param int<0, 4294967295> $length
     *
     * @return IOFrame<Frame>
     */
    private function readBody(
        Channel $channel,
        int $length,
    ): IOFrame {
        return (match ($length) {
            0 => IOFrame\NoOp::of(Str::of('')),
            default => IOFrame\Chunk::of($length),
        })
            ->map(static fn($data) => Frame::body($channel, $data));
    }
}
