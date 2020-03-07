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
use Psr\Log\LoggerInterface;

final class Logger implements BasicInterface
{
    private BasicInterface $basic;
    private LoggerInterface $logger;

    public function __construct(BasicInterface $basic, LoggerInterface $logger)
    {
        $this->basic = $basic;
        $this->logger = $logger;
    }

    public function ack(Ack $command): void
    {
        $this->basic->ack($command);
    }

    public function cancel(Cancel $command): void
    {
        $this->basic->cancel($command);
    }

    public function consume(Consume $command): Consumer
    {
        return new Consumer\Logger(
            $this->basic->consume($command),
            $this->logger,
        );
    }

    public function get(GetCommand $command): Get
    {
        return new Get\Logger(
            $this->basic->get($command),
            $this->logger,
        );
    }

    public function publish(Publish $command): void
    {
        $this->basic->publish($command);
    }

    public function qos(Qos $command): void
    {
        $this->basic->qos($command);
    }

    public function recover(Recover $command): void
    {
        $this->basic->recover($command);
    }

    public function reject(Reject $command): void
    {
        $this->basic->reject($command);
    }
}
