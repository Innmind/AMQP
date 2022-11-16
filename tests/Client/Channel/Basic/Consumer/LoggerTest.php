<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Basic\Consumer;

use Innmind\AMQP\{
    Client\Channel\Basic\Consumer\Logger,
    Client\Channel\Basic\Consumer,
    Model\Basic\Message,
    Exception\Reject,
    Exception\Requeue,
    Exception\Cancel,
};
use Innmind\Immutable\Str;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Consumer::class,
            new Logger(
                $this->createMock(Consumer::class),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testTake()
    {
        $consumer = new Logger(
            $inner = $this->createMock(Consumer::class),
            $this->createMock(LoggerInterface::class),
        );
        $inner
            ->expects($this->once())
            ->method('take')
            ->with(42);

        $this->assertNull($consumer->take(42));
    }

    public function testFilter()
    {
        $message = static fn($i) => Message::of(Str::of('foobar'.$i));

        $consumer = new Logger(
            new class($message) implements Consumer {
                private $message;
                private $predicate;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function foreach(callable $consume): void
                {
                    foreach (\range(0, 1) as $i) {
                        ($this->predicate)(($this->message)($i), false, '', '');
                    }
                }

                public function take(int $number): void
                {
                }

                public function filter(callable $predicate): void
                {
                    $this->predicate = $predicate;
                }
            },
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'AMQP message was filtered',
                ['body' => 'foobar1'],
            );

        $filter = false;
        $consumer->filter(static function() use (&$filter): bool {
            return $filter = !$filter;
        });
        $consumer->foreach(static function() {});
    }

    public function testLogMessageReceived()
    {
        $message = Message::of(Str::of('foobar'));

        $consumer = new Logger(
            new class($message) implements Consumer {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function foreach(callable $consume): void
                {
                    $consume($this->message, false, '', '');
                }

                public function take(int $number): void
                {
                }
                public function filter(callable $predicate): void
                {
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

        $consumer->foreach(static function() {});
    }

    public function testLogReject()
    {
        $message = Message::of(Str::of('foobar'));

        $consumer = new Logger(
            new class($message) implements Consumer {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function foreach(callable $consume): void
                {
                    try {
                        $consume($this->message, false, '', '');
                    } catch (Reject $e) {
                        //pass
                    }
                }

                public function take(int $number): void
                {
                }
                public function filter(callable $predicate): void
                {
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

        $consumer->foreach(static function() {
            throw new Reject;
        });
    }

    public function testLogRequeue()
    {
        $message = Message::of(Str::of('foobar'));

        $consumer = new Logger(
            new class($message) implements Consumer {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function foreach(callable $consume): void
                {
                    try {
                        $consume($this->message, false, '', '');
                    } catch (Requeue $e) {
                        //pass
                    }
                }

                public function take(int $number): void
                {
                }
                public function filter(callable $predicate): void
                {
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

        $consumer->foreach(static function() {
            throw new Requeue;
        });
    }

    public function testLogCancel()
    {
        $message = Message::of(Str::of('foobar'));

        $consumer = new Logger(
            new class($message) implements Consumer {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function foreach(callable $consume): void
                {
                    try {
                        $consume($this->message, false, '', '');
                    } catch (Cancel $e) {
                        //pass
                    }
                }

                public function take(int $number): void
                {
                }
                public function filter(callable $predicate): void
                {
                }
            },
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'AMQP consumer canceled',
                ['body' => 'foobar'],
            );

        $consumer->foreach(static function() {
            throw new Cancel;
        });
    }

    public function testLogError()
    {
        $message = Message::of(Str::of('foobar'));

        $consumer = new Logger(
            new class($message) implements Consumer {
                private $message;

                public function __construct($message)
                {
                    $this->message = $message;
                }

                public function foreach(callable $consume): void
                {
                    try {
                        $consume($this->message, false, '', '');
                    } catch (\RuntimeException $e) {
                        //pass
                    }
                }

                public function take(int $number): void
                {
                }
                public function filter(callable $predicate): void
                {
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

        $consumer->foreach(static function() {
            throw new \RuntimeException;
        });
    }
}
