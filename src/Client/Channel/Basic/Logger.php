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
    Model\Basic\Message
};
use Psr\Log\LoggerInterface;

final class Logger implements BasicInterface
{
    private $basic;
    private $logger;

    public function __construct(BasicInterface $basic, LoggerInterface $logger)
    {
        $this->basic = $basic;
        $this->logger = $logger;
    }

    public function ack(Ack $command): BasicInterface
    {
        $this->basic->ack($command);

        return $this;
    }

    public function cancel(Cancel $command): BasicInterface
    {
        $this->basic->cancel($command);

        return $this;
    }

    public function consume(Consume $command): Consumer
    {
        return new Consumer\Logger(
            $this->basic->consume($command),
            $this->logger
        );
    }

    public function get(GetCommand $command): Get
    {
        return new Get\Logger(
            $this->basic->get($command),
            $this->logger
        );
    }

    public function publish(Publish $command): BasicInterface
    {
        $this->basic->publish($command);

        return $this;
    }

    public function qos(Qos $command): BasicInterface
    {
        $this->basic->qos($command);

        return $this;
    }

    public function recover(Recover $command): BasicInterface
    {
        $this->basic->recover($command);

        return $this;
    }

    public function reject(Reject $command): BasicInterface
    {
        $this->basic->reject($command);

        return $this;
    }
}
