<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic;

use Innmind\AMQP\{
    Client\Channel\Basic as BasicInterface,
    Model\Basic\Ack,
    Model\Basic\Cancel,
    Model\Basic\Consume,
    Model\Basic\Get as GetCommand,
    Model\Basic\Publish,
    Model\Basic\Qos,
    Model\Basic\Recover,
    Model\Basic\Reject,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Value,
    Transport\Frame\Method,
};

final class Basic implements BasicInterface
{
    private Connection $connection;
    private Channel $channel;
    private MessageReader $read;

    public function __construct(Connection $connection, Channel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
        $this->read = new MessageReader;
    }

    public function ack(Ack $command): void
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->ack(
                $this->channel,
                $command,
            ),
        );
    }

    public function cancel(Cancel $command): void
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->cancel(
                $this->channel,
                $command,
            ),
        );

        if ($command->shouldWait()) {
            $this->connection->wait(Method::basicCancelOk);
        }
    }

    public function consume(Consume $command): Consumer
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->consume(
                $this->channel,
                $command,
            ),
        );

        if ($command->shouldWait()) {
            $frame = $this->connection->wait(Method::basicConsumeOk);
            /** @var Value\ShortString */
            $consumerTag = $frame->values()->first()->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            $consumerTag = $consumerTag->original()->toString();
        } else {
            $consumerTag = $command->consumerTag()->match(
                static fn($tag) => $tag,
                static fn() => throw new \LogicException,
            );
        }

        return new Consumer\Consumer(
            $this->connection,
            $command,
            $this->channel,
            $consumerTag,
        );
    }

    public function get(GetCommand $command): Get
    {
        $this->connection->send($this->connection->protocol()->basic()->get(
            $this->channel,
            $command,
        ));
        $frame = $this->connection->wait(Method::basicGetOk, Method::basicGetEmpty);

        if ($frame->is(Method::basicGetEmpty)) {
            return new Get\GetEmpty;
        }

        $message = ($this->read)($this->connection);
        /** @var Value\UnsignedLongLongInteger */
        $deliveryTag = $frame->values()->first()->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        /** @var Value\Bits */
        $redelivered = $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        /** @var Value\ShortString */
        $exchange = $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        /** @var Value\ShortString */
        $routingKey = $frame->values()->get(3)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        /** @var Value\UnsignedLongInteger */
        $messageCount = $frame->values()->get(4)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );

        return new Get\GetOk(
            $this->connection,
            $this->channel,
            $command,
            $message,
            $deliveryTag->original()->value(),
            $redelivered->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => throw new \LogicException,
            ),
            $exchange->original()->toString(),
            $routingKey->original()->toString(),
            $messageCount->original()->value(),
        );
    }

    public function publish(Publish $command): void
    {
        $_ = $this
            ->connection
            ->protocol()
            ->basic()
            ->publish(
                $this->channel,
                $command,
                $this->connection->maxFrameSize(),
            )
            ->foreach(function(Frame $frame): void {
                $this->connection->send($frame);
            });
    }

    public function qos(Qos $command): void
    {
        $this->connection->send($this->connection->protocol()->basic()->qos(
            $this->channel,
            $command,
        ));
        $this->connection->wait(Method::basicQosOk);
    }

    public function recover(Recover $command): void
    {
        $this->connection->send($this->connection->protocol()->basic()->recover(
            $this->channel,
            $command,
        ));
        $this->connection->wait(Method::basicRecoverOk);
    }

    public function reject(Reject $command): void
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->reject(
                $this->channel,
                $command,
            ),
        );
    }
}
