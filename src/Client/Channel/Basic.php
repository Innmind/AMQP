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
    Message,
};

interface Basic
{
    public function ack(Ack $command): void;
    public function cancel(Cancel $command): void;
    public function consume(Consume $command): Basic\Consumer;
    public function get(Get $command): Basic\Get;
    public function publish(Publish $command): void;
    public function qos(Qos $command): void;
    public function recover(Recover $command): void;
    public function reject(Reject $command): void;
}
