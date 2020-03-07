<?php
declare(strict_types = 1);

use Innmind\AMQP\{
    Model\Queue\Declaration as Queue,
    Model\Queue\Binding,
    Model\Exchange\Declaration as Exchange,
    Model\Exchange\Type,
    Model\Basic\Consume,
    Exception\Cancel
};

$client = require 'client.php';

$channel = $client->channel();
$channel
    ->queue()
    ->declare(
        Queue::temporary()
            ->withName('bench_queue')
    );
$channel
    ->exchange()
    ->declare(
        Exchange::temporary('bench_exchange', Type::direct())
    );
$channel
    ->queue()
    ->bind(
        new Binding('bench_exchange', 'bench_queue')
    );
$consumer = $channel
    ->basic()
    ->consume(
        (new Consume('bench_queue'))
            ->autoAcknowledge()
    );

$start = microtime(true);
$count = 0;

$consumer->foreach(static function($message) use (&$count): void {
    if ($message->body()->toString() === 'quit') {
        throw new Cancel;
    }

    ++$count;
});

printf("Pid: %s, Count: %s, Time: %.4f\n", getmypid(), $count, microtime(true) - $start);

$client->close();
