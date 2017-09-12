<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

use Innmind\AMQP\Model\Basic\{
    Ack,
    Cancel,
    Consume,
    Get,
    Publish,
    Qos,
    Recover,
    Reject,
    Message
};

interface Basic
{
    public function ack(Ack $command): self;
    public function cancel(Cancel $command): self;
    public function consume(Consume $command): Basic\Consumer;
    public function get(Get $command): Basic\Get;
    public function publish(Publish $command): self;
    public function qos(Qos $command): self;
    public function recover(Recover $command): self;
    public function reject(Reject $command): self;
}
