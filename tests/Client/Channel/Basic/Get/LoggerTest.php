<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Basic\Get;

use Innmind\AMQP\{
    Client\Channel\Basic\Get\Logger,
    Client\Channel\Basic\Get,
    Model\Basic\Message,
    Exception\Reject,
    Exception\Requeue,
};
use Innmind\Immutable\Str;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Get::class,
            new Logger(
                $this->createMock(Get::class),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testLogMessageReceived()
    {
        $message = Message::of(Str::of('foobar'));

        $consume = new Logger(
            new class($message) implements Get {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function __invoke(callable $consume): void
                {
                    $consume($this->message, false, '', '', 0);
                }
            },
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'AMQP message received',
                ['body' => 'foobar'],
            );

        $consume(static function() {});
    }

    public function testLogReject()
    {
        $message = Message::of(Str::of('foobar'));

        $consume = new Logger(
            new class($message) implements Get {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function __invoke(callable $consume): void
                {
                    try {
                        $consume($this->message, false, '', '', 0);
                    } catch (Reject $e) {
                        //pass
                    }
                }
            },
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'AMQP message rejected',
                ['body' => 'foobar'],
            );

        $consume(static function() {
            throw new Reject;
        });
    }

    public function testLogRequeue()
    {
        $message = Message::of(Str::of('foobar'));

        $consume = new Logger(
            new class($message) implements Get {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function __invoke(callable $consume): void
                {
                    try {
                        $consume($this->message, false, '', '', 0);
                    } catch (Requeue $e) {
                        //pass
                    }
                }
            },
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'AMQP message requeued',
                ['body' => 'foobar'],
            );

        $consume(static function() {
            throw new Requeue;
        });
    }

    public function testLogError()
    {
        $message = Message::of(Str::of('foobar'));

        $consume = new Logger(
            new class($message) implements Get {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function __invoke(callable $consume): void
                {
                    try {
                        $consume($this->message, false, '', '', 0);
                    } catch (\RuntimeException $e) {
                        //pass
                    }
                }
            },
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'AMQP message consumption generated an exception',
                [
                    'body' => 'foobar',
                    'exception' => 'RuntimeException',
                ],
            );

        $consume(static function() {
            throw new \RuntimeException;
        });
    }
}
