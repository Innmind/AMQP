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
    public function ack(Ack $command): void
    {
        // pass
    }

    public function cancel(Cancel $command): void
    {
        // pass
    }

    public function consume(Consume $command): Consumer
    {
        return new Consumer\NullConsumer;
    }

    public function get(GetCommand $command): Get
    {
        return new Get\GetEmpty;
    }

    public function publish(Publish $command): void
    {
        // pass
    }

    public function qos(Qos $command): void
    {
        // pass
    }

    public function recover(Recover $command): void
    {
        // pass
    }

    public function reject(Reject $command): void
    {
        // pass
    }
}
