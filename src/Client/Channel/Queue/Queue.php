<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Queue;

use Innmind\AMQP\{
    Client\Channel\Queue as QueueInterface,
    Model\Queue\Declaration,
    Model\Queue\DeclareOk,
    Model\Queue\Deletion,
    Model\Queue\DeleteOk,
    Model\Queue\Binding,
    Model\Queue\Unbinding,
    Model\Queue\Purge,
    Model\Queue\PurgeOk,
    Model\Count,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Value,
    Transport\Frame\Method,
};

final class Queue implements QueueInterface
{
    private Connection $connection;
    private Channel $channel;

    public function __construct(Connection $connection, Channel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    public function declare(Declaration $command): ?DeclareOk
    {
        return $this
            ->connection
            ->send(fn($protocol) => $protocol->queue()->declare(
                $this->channel,
                $command,
            ))
            ->maybeWait($command->shouldWait(), Method::queueDeclareOk)
            ->match(
                static function($_, $frame) {
                    /** @var Value\ShortString */
                    $name = $frame->values()->get(0)->match(
                        static fn($value) => $value,
                        static fn() => throw new \LogicException,
                    );
                    /** @var Value\UnsignedLongInteger */
                    $message = $frame->values()->get(1)->match(
                        static fn($value) => $value,
                        static fn() => throw new \LogicException,
                    );
                    /** @var Value\UnsignedLongInteger */
                    $consumer = $frame->values()->get(2)->match(
                        static fn($value) => $value,
                        static fn() => throw new \LogicException,
                    );

                    return DeclareOk::of(
                        $name->original()->toString(),
                        Count::of($message->original()),
                        Count::of($consumer->original()),
                    );
                },
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    public function delete(Deletion $command): ?DeleteOk
    {
        return $this
            ->connection
            ->send(fn($protocol) => $protocol->queue()->delete(
                $this->channel,
                $command,
            ))
            ->maybeWait($command->shouldWait(), Method::queueDeleteOk)
            ->match(
                static function($_, $frame) {
                    /** @var Value\UnsignedLongInteger */
                    $message = $frame->values()->first()->match(
                        static fn($value) => $value,
                        static fn() => throw new \LogicException,
                    );

                    return DeleteOk::of(Count::of(
                        $message->original(),
                    ));
                },
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    public function bind(Binding $command): void
    {
        $_ = $this
            ->connection
            ->send(fn($protocol) => $protocol->queue()->bind(
                $this->channel,
                $command,
            ))
            ->maybeWait($command->shouldWait(), Method::queueBindOk)
            ->match(
                static fn() => null,
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    public function unbind(Unbinding $command): void
    {
        $_ = $this
            ->connection
            ->send(fn($protocol) => $protocol->queue()->unbind(
                $this->channel,
                $command,
            ))
            ->wait(Method::queueUnbindOk)
            ->match(
                static fn() => null,
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    public function purge(Purge $command): ?PurgeOk
    {
        return $this
            ->connection
            ->send(fn($protocol) => $protocol->queue()->purge(
                $this->channel,
                $command,
            ))
            ->maybeWait($command->shouldWait(), Method::queuePurgeOk)
            ->match(
                static function($_, $frame) {
                    /** @var Value\UnsignedLongInteger */
                    $message = $frame->values()->first()->match(
                        static fn($value) => $value,
                        static fn() => throw new \LogicException,
                    );

                    return PurgeOk::of(Count::of(
                        $message->original(),
                    ));
                },
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }
}
