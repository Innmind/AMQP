# AMQP

[![Build Status](https://github.com/Innmind/AMQP/workflows/CI/badge.svg?branch=master)](https://github.com/Innmind/AMQP/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/AMQP/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/AMQP)
[![Type Coverage](https://shepherd.dev/github/Innmind/AMQP/coverage.svg)](https://shepherd.dev/github/Innmind/AMQP)

This is an AMQP client implementing the version `0.9` of the protocol.

The goal of this implementation is to provide a PHP land implementation (for ease of use and readability)  with a clear separation between the AMQP Model, transport layer and user API.

**Note**: This implementation couldn't have been done without [`php-amqplib`](https://packagist.org/packages/php-amqplib/php-amqplib) that helped a lot to figure out the details of the transport layer.

**Important**: If you are using RabbitMQ be aware that it doesn't implemented the specification completely, `Qos` and `Recover` methods are not implemented. And if you find yourself using [`Value`](src/Transport/Frame/Value.php) implementations note that `ShortString`, `SignedLongLongInteger` and `SignedShortInteger` generate server errors on some methods (like using them as message headers).

## Installation

```sh
composer require innmind/amqp
```

## Usage

```php
use Innmind\AMQP\{
    Factory,
    Command\DeclareExchange,
    Command\DeclareQueue,
    Command\Bind,
    Command\Publish,
    Model\Basic as Model,
    Model\Basic\Message,
    Model\Exchange\Type,
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\OperatingSystem\Factory as OSFactory;
use Innmind\Url\Url;
use Innmind\Immutable\Str;

$os = OSFactory::build();
$client = Factory::of($os)
    ->make(
        Transport::tcp(),
        Url::of('amqp://guest:guest@localhost:5672/'),
        new ElapsedPeriod(1000), // timeout
    )
    ->with(DeclareExchange::of('crawler', Type::direct))
    ->with(DeclareQueue::of('parser'))
    ->with(Bind::of('crawler', 'parser'))
    ->with(Publish::one(Model\Publish::a(Message::of(Str::of('https://github.com')))))
    ->run(null)
    ->match(
        static fn() => null, // success
        static fn($failure) => throw new \RuntimeException($failure::class),
    );
```

The above example will declare an exchange named `crawler` and queue `parser` that will receive messages from our exchange. Finally it will publish a message with the payload `http://github.com/` to `crawler` (and the server will route it to `parser`).

And to consume the messages you have 2 approaches:

```php
use Innminq\AMQP\{
    Command\Get,
    Command\Consume,
    Consumer\Continuation,
    Model\Basic\Message,
};

$state = $client
    ->with(Get::of('parser')->handle(static function($state, Message $message, Continuation $continuation) {
        $state = $message->body()->toString();

        return $continuation->ack($state);
    }))
    ->run(null) // <- this argument will passed as the state to the handler above
    ->match(
        static fn($state) => $state,
        static fn($failure) => throw new \RuntimeException($failure::class),
    );
echo $state; // will print "http://github.com/"
// or
$client
    ->with(Consume::of('crawler')->handle(static function($state, Message $message, Continuation $continuation) {
        doStuff($message);

        return $continuation->reject($state); // to reject the message
        return $continuation->requeue($state); // put the message back in the queue so it can be redelivered
        return $continuation->cancel($state); // instruct to stop receiving messages (current will be acknowledged first)
    }))
    ->run(null)
    ->match(
        static fn() => null, // in this case only reachable when you cancel the consumer
        static fn($failure) => throw new \RuntimeException($failure::class),
    );
```

`reject()` and `requeue()` can also be used in the `get` callback.

Feel free to look at the `Command` namespace to explore all capabilities.

## Benchmarks

`make benchmark` run on a MacBookPro18,2 (M1 Max, 32Gb RAM) with a RabbitMQ running in a container (via docker for mac) produces this result:

```
make benchmark
Publishing 4000 msgs with 1KB of content:
php benchmark/producer.php 4000
0.39038109779358
Consuming 4000:
php benchmark/consumer.php
Pid: 701, Count: 4000, Time: 1.6017
```

By comparison, the `php-amqplib` produces this result:

```
Publishing 4000 msgs with 1KB of content:
php benchmark/producer.php 4000
0.15483689308167
Consuming 4000:
php benchmark/consumer.php
Pid: 46862, Count: 4000, Time: 0.2366
```

So it appears _pure_ functions come at a cost!

**Note**: both benchmarks use manual acknowledgement of messages
