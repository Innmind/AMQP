<?php
declare(strict_types = 1);

use Innmind\AMQP\{
    Command\DeclareQueue,
    Command\DeclareExchange,
    Command\Bind,
    Command\Consume,
    Model\Exchange\Type,
};

$client = require 'client.php';

$client = $client
    ->with(DeclareQueue::of('bench_queue'))
    ->with(DeclareExchange::of('bench_exchange', Type::direct))
    ->with(Bind::of('bench_exchange', 'bench_queue'))
    ->with(
        Consume::of('bench_queue')->handle(function(int $count, $message, $continuation) {
            return match ($message->body()->toString()) {
                'quit' => $continuation->cancel($count),
                default => $continuation->ack($count + 1),
            };
        }),
    );

$start = microtime(true);
$count = $client
    ->run(0)
    ->match(
        static fn($count) => $count,
        static fn($failure) => throw new \RuntimeException($failure::class),
    );

printf("Pid: %s, Count: %s, Time: %.4f\n", getmypid(), $count, microtime(true) - $start);
