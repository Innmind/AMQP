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
    Model\Basic\Message,
};

final class NullBasic implements BasicInterface
{
    public function ack(Ack $command): BasicInterface
    {
        return $this;
    }

    public function cancel(Cancel $command): BasicInterface
    {
        return $this;
    }

    public function consume(Consume $command): Consumer
    {
        return new Consumer\NullConsumer;
    }

    public function get(GetCommand $command): Get
    {
        return new Get\GetEmpty;
    }

    public function publish(Publish $command): BasicInterface
    {
        return $this;
    }

    public function qos(Qos $command): BasicInterface
    {
        return $this;
    }

    public function recover(Recover $command): BasicInterface
    {
        return $this;
    }

    public function reject(Reject $command): BasicInterface
    {
        return $this;
    }
}
