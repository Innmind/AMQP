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
    private $consumer;
    private $logger;

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
        $this->consumer->foreach(function(Message $message, ...$args) use ($consume): void {
            try {
                $this->logger->debug(
                    'AMQP message received',
                    ['body' => (string) $message->body()]
                );

                $consume($message, ...$args);
            } catch (Reject $e) {
                $this->logger->warning(
                    'AMQP message rejected',
                    ['body' => (string) $message->body()]
                );
                throw $e;
            } catch (Requeue $e) {
                $this->logger->info(
                    'AMQP message requeued',
                    ['body' => (string) $message->body()]
                );
                throw $e;
            } catch (Cancel $e) {
                $this->logger->warning(
                    'AMQP consumer canceled',
                    ['body' => (string) $message->body()]
                );
                throw $e;
            } catch (\Throwable $e) {
                $this->logger->error(
                    'AMQP message consumption generated an exception',
                    [
                        'body' => (string) $message->body(),
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

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): ConsumerInterface
    {
        $this->consumer = $this->consumer->filter(function(Message $message, ...$args) use ($predicate): bool {
            $return = $predicate($message, ...$args);

            if (!$return) {
                $this->logger->info(
                    'AMQP message was filtered',
                    ['body' => (string) $message->body()]
                );
            }

            return $return;
        });

        return $this;
    }
}
