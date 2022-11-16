<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Model\Connection\MaxFrameSize,
    Exception\ExpectedMethodFrame,
    Exception\ConnectionClosed,
    Exception\UnexpectedFrame,
    Exception\FrameChannelExceedAllowedChannelNumber,
    Exception\FrameExceedAllowedSize,
};
use Innmind\Immutable\Maybe;

interface Connection
{
    public function protocol(): Protocol;

    /**
     * When it contains the same connection instance it means that you can still
     * use the connection, otherwise you should stop using it
     *
     * Possible failures are exceeding the max channel number, the max frame size
     * or that writting to the socket failed
     *
     * @return Maybe<$this>
     */
    public function send(Frame $frame): Maybe;

    /**
     * @throws ExpectedMethodFrame When expecting a method frame but another type is received
     * @throws ConnectionClosed When the server sent a connection.close method
     * @throws UnexpectedFrame When the received frame is not one of the expected one
     */
    public function wait(Frame\Method ...$names): Frame;
    public function maxFrameSize(): MaxFrameSize;
    public function close(): void;
    public function closed(): bool;
}
