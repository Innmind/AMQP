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
    Model\Basic\Message\Locked,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame,
    Transport\Frame\Channel,
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

    public function ack(Ack $command): BasicInterface
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->ack(
                $this->channel,
                $command
            )
        );

        return $this;
    }

    public function cancel(Cancel $command): BasicInterface
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->cancel(
                $this->channel,
                $command
            )
        );

        if ($command->shouldWait()) {
            $this->connection->wait('basic.cancel-ok');
        }

        return $this;
    }

    public function consume(Consume $command): Consumer
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->consume(
                $this->channel,
                $command
            )
        );

        if ($command->shouldWait()) {
            $frame = $this->connection->wait('basic.consume-ok');
            $consumerTag = $frame->values()->first()->original()->toString();
        } else {
            $consumerTag = $command->consumerTag();
        }

        return new Consumer\Consumer(
            $this->connection,
            $command,
            $this->channel,
            $consumerTag
        );
    }

    public function get(GetCommand $command): Get
    {
        $frame = $this
            ->connection
            ->send($this->connection->protocol()->basic()->get(
                $this->channel,
                $command
            ))
            ->wait('basic.get-ok', 'basic.get-empty');

        if ($frame->is($this->connection->protocol()->method('basic.get-empty'))) {
            return new Get\GetEmpty;
        }

        $message = ($this->read)($this->connection);

        return new Get\GetOk(
            $this->connection,
            $this->channel,
            $command,
            new Locked($message),
            $frame->values()->first()->original()->value(), //deliveryTag
            $frame->values()->get(1)->original()->first(), //redelivered
            $frame->values()->get(2)->original()->toString(), //exchange
            $frame->values()->get(3)->original()->toString(), //routingKey
            $frame->values()->get(4)->original()->value() //messageCount
        );
    }

    public function publish(Publish $command): BasicInterface
    {
        $this
            ->connection
            ->protocol()
            ->basic()
            ->publish(
                $this->channel,
                $command,
                $this->connection->maxFrameSize()
            )
            ->foreach(function(Frame $frame): void {
                $this->connection->send($frame);
            });

        return $this;
    }

    public function qos(Qos $command): BasicInterface
    {
        $this
            ->connection
            ->send($this->connection->protocol()->basic()->qos(
                $this->channel,
                $command
            ))
            ->wait('basic.qos-ok');

        return $this;
    }

    public function recover(Recover $command): BasicInterface
    {
        $this
            ->connection
            ->send($this->connection->protocol()->basic()->recover(
                $this->channel,
                $command
            ))
            ->wait('basic.recover-ok');

        return $this;
    }

    public function reject(Reject $command): BasicInterface
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->reject(
                $this->channel,
                $command
            )
        );

        return $this;
    }
}
