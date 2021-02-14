# AMQP

[![Build Status](https://github.com/Innmind/AMQP/workflows/CI/badge.svg?branch=master)](https://github.com/Innmind/AMQP/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/AMQP/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/AMQP)
[![Type Coverage](https://shepherd.dev/github/Innmind/AMQP/coverage.svg)](https://shepherd.dev/github/Innmind/AMQP)

This is an AMQP client implementing the version `0.9` of the protocol. Even though the `1.0` is out it's not implemented (yet?) as RabbitMQ is still on 0.9 (despite that the code has been structured so it can be easy to create this implementation).

The goal of this implementation is to provide a PHP land (so can be easier to extend it, or simply read it; countrary to a PECL package IMO). The code is stateless (except [`Get`](src/Client/Channel/Basic/Get.php) and [`Consumer`](src/Client/Channel/Basic/Consumer.php)) with a clear separation between the AMQP Model, transport layer and user API.

**Note**: This implementation couldn't have been done without [`php-amqplib`](https://packagist.org/packages/php-amqplib/php-amqplib) that helped a lot to figure out the details of the transport layer.

**Important**: If you are using RabbitMQ be aware that it doesn't implemented the specification completely, `Qos` and `Recover` methods are not implemented. And if you find yourself using [`Value`](src/Transport/Frame/Value.php) implementations note that `ShortString`, `SignedLongLongInteger` and `SignedShortInteger` generate server errors on some methods (like using them as message headers).

## Installation

```sh
composer require innmind/amqp
```

## Usage

```php
use function Innmind\AMQP\bootstrap;
use Innmind\AMQP\{
    Model\Exchange\Declaration as Exchange,
    Model\Exchange\Type,
    Model\Queue\Declaration as Queue,
    Model\Queue\Binding,
    Model\Basic\Message\Generic as Message,
    Model\Basic\Publish,
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;
use Innmind\Immutable\Str;

$os = Factory::build();
$amqp = bootstrap();
$client = $amqp['client']['basic'](
    Transport::tcp(),
    Url::of('amqp://guest:guest@localhost:5672/'),
    new ElapsedPeriod(1000), // timeout
    $os->clock(),
    $os->process(),
    $os->remote(),
    $os->sockets(),
);
$client
    ->channel()
    ->exchange()
    ->declare(
        Exchange::durable('crawler', Type::direct()),
    );
$client
    ->channel()
    ->queue()
    ->declare(
        Queue::durable()->withName('parser'),
    );
$client
    ->channel()
    ->queue()
    ->bind(
        new Binding('crawler', 'parser'),
    );
$message = new Message(Str::of('http://github.com/'));
$client
    ->channel()
    ->basic()
    ->publish(
        Publish::a($message)->to('crawler'),
    );
```

The above example will declare an exchange named `crawler` and queue `parser` that will receive messages our exchange. Finally it will publish a message with the payload `http://github.com/` to `crawler` (and the server will route it to `parser`).

And to consume the messages you have 2 approaches:

```php
use Innminq\AMQP\{
    Model\Basic\Get,
    Model\Basic\Consume,
    Exception\Reject,
    Exception\Requeue,
    Exception\Cancel,
};

$client
    ->channel()
    ->basic()
    ->get(new Get('parser'))(static function(Message $message): void {
        echo $message->body()->toString(); // will print "http://github.com/"
    }); // consume only one message
// or
$consumers = $client
    ->channel()
    ->basic()
    ->consume(new Consume('crawler'));
$consumer->take(42); // consume only 42 messages, if omitted it will run as long the connection is opened
$consumer->filter(static function(Message $message): bool {
    return $message->body()->matches('~^https://~'); // only use this when server routing is no longer enough
});
$consumer->foreach(static function(Message $message): void {
    doStuff($message->body());

    throw new Reject; // to reject the message
    throw new Requeue; // put the message back in the queue so it can be redelivered
    throw new Cancel; // instruct to stop receiving messages
});
```

`Reject` and `Requeue` can also be used in the `get` callback.

Feel free to look at the client interfaces to explore all capabilities.

**Note**: all the calls to `$client->channel()` always return the same instance, meaning it's the same AMQP channel. The default implementation is one channel per PHP process, this is done to keep the code simple (otherwise it's harder to route received frames to the wished code).

Once you're finished making calls you can simply call `$client->close()` that will perform a graceful shutdown by closing all the channels then the AMQP connection and in the end the socket.

## Friendlier usage

By default if you send commands via the client but the connection is closed it will throw exceptions saying it can't send the data. This can complexify your code as you need to catch those exceptions.

If you want the client to fail silenty you can simply decorate the client like so:

```php
$client = $amqp['client']['fluent']($client);
```

This will for example allow you to _consume_ a queue but in reality will do nothing if the connection is closed, of course if it's opened it will receive messages like before.

## Logging

By default no activity is logged when using this library, but you have 2 strategies to log what's happening: at the transport layer or at the client level. This is done either by decorating the connection object or the client one:

```php
use Psr\Log\LoggerInterface;

$amqp = bootstrap();
$client = $amqp['client']['logger']($baseClient, /* instance of LoggerInterface */);
```

By decorating the connection it will log every sent and received frames, do this if you want to know what's sent through the wire. By decorating the client it will log every received messages and if they've been rejected or requeued; explicit cancel calls (via the exception) and errors thrown during message consumption will be as well.

Of course you can use both at the same time if you want to be thorough.

## Benchmarks

`make benchmark` run on a MacBookPro11,2 (2GHz, 8Gb RAM) with a RabbitMQ running in a container (via docker for mac) produces this result:

```
Publishing 4000 msgs with 1KB of content:
php benchmark/producer.php 4000
0.58265900611877
Consuming 4000:
php benchmark/consumer.php
Pid: 67408, Count: 4000, Time: 1.8081
Stream produce 100:
php benchmark/stream_tmp_produce.php 100
0.22138905525208
```

By comparison, the `php-amqplib` produces this result:

```
Publishing 4000 msgs with 1KB of content:
php benchmark/producer.php 4000
0.13555884361267
Consuming 4000:
php benchmark/consumer.php
Pid: 9227, Count: 4000, Time: 0.5299
Stream produce 100:
php benchmark/stream_tmp_produce.php 100
0.29217886924744
```

So it appears _pure_ functions come at a cost!
