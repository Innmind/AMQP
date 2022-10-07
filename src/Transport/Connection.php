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

interface Connection
{
    public function protocol(): Protocol;

    /**
     * @throws FrameChannelExceedAllowedChannelNumber
     * @throws FrameExceedAllowedSize
     */
    public function send(Frame $frame): void;

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
