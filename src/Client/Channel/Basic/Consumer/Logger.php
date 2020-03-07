<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic\Consumer;

use Innmind\AMQP\{
    Client\Channel\Basic\Consumer as ConsumerInterface,
    Model\Basic\Message,
    Exception\Reject,
    Exception\Requeue,
    Exception\Cancel,
};
use Psr\Log\LoggerInterface;

final class Logger implements ConsumerInterface
{
    private ConsumerInterface $consumer;
    private LoggerInterface $logger;

    public function __construct(
        ConsumerInterface $consumer,
        LoggerInterface $logger
    ) {
        $this->consumer = $consumer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $consume): void
    {
        $this->consumer->foreach(function(
            Message $message,
            bool $redelivered,
            string $exchange,
            string $routingKey
        ) use (
            $consume
        ): void {
            try {
                $this->logger->debug(
                    'AMQP message received',
                    ['body' => $message->body()->toString()]
                );

                $consume($message, $redelivered, $exchange, $routingKey);
            } catch (Reject $e) {
                $this->logger->warning(
                    'AMQP message rejected',
                    ['body' => $message->body()->toString()]
                );
                throw $e;
            } catch (Requeue $e) {
                $this->logger->info(
                    'AMQP message requeued',
                    ['body' => $message->body()->toString()]
                );
                throw $e;
            } catch (Cancel $e) {
                $this->logger->warning(
                    'AMQP consumer canceled',
                    ['body' => $message->body()->toString()]
                );
                throw $e;
            } catch (\Throwable $e) {
                $this->logger->error(
                    'AMQP message consumption generated an exception',
                    [
                        'body' => $message->body()->toString(),
                        'exception' => \get_class($e),
                    ]
                );
                throw $e;
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function take(int $count): ConsumerInterface
    {
        $this->consumer = $this->consumer->take($count);

        return $this;
    }

    public function filter(callable $predicate): ConsumerInterface
    {
        $this->consumer = $this->consumer->filter(function(
            Message $message,
            bool $redelivered,
            string $exchange,
            string $routingKey
        ) use (
            $predicate
        ): bool {
            $return = $predicate($message, $redelivered, $exchange, $routingKey);

            if (!$return) {
                $this->logger->info(
                    'AMQP message was filtered',
                    ['body' => $message->body()->toString()]
                );
            }

            return $return;
        });

        return $this;
    }
}
