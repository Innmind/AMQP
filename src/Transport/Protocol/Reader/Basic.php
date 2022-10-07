<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\ChunkArguments,
    Transport\Frame\Value,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Protocol\Methods,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class Basic
{
    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        switch (true) {
            case Method::basicQosOk->equals($method):
                $chunk = $this->qosOk();
                break;

            case Method::basicConsumeOk->equals($method):
                $chunk = $this->consumeOk();
                break;

            case Method::basicCancelOk->equals($method):
                $chunk = $this->cancelOk();
                break;

            case Method::basicReturn->equals($method):
                $chunk = $this->return();
                break;

            case Method::basicDeliver->equals($method):
                $chunk = $this->deliver();
                break;

            case Method::basicGetOk->equals($method):
                $chunk = $this->getOk();
                break;

            case Method::basicGetEmpty->equals($method):
                $chunk = $this->getEmpty();
                break;

            case Method::basicRecoverOk->equals($method):
                $chunk = $this->recoverOk();
                break;

            default:
                throw new \RuntimeException;
        }

        return $chunk($arguments);
    }

    private function qosOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function consumeOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, // consumer tag
        );
    }

    private function cancelOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, // consumer tag
        );
    }

    private function return(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, // reply code
            ShortString::class, // reply text
            ShortString::class, // exchange
            ShortString::class, // routing key
        );
    }

    private function deliver(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, // consumer tag
            UnsignedLongLongInteger::class, // delivery tag
            Bits::class, // redelivered
            ShortString::class, // exchange
            ShortString::class, // routing key
        );
    }

    private function getOk(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedLongLongInteger::class, // delivery tag
            Bits::class, // redelivered
            ShortString::class, // exchange
            ShortString::class, // routing key
            UnsignedLongInteger::class, // message count
        );
    }

    private function getEmpty(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function recoverOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }
}
