<?php
declare(strict_types = 1);

use Innmind\AMQP\{
    Command\DeclareQueue,
    Command\DeclareExchange,
    Command\Bind,
    Command\Publish,
    Model\Basic as Model,
    Model\Basic\Message,
    Model\Exchange\Type,
};
use Innmind\Immutable\{
    Str,
    Sequence,
};

$client = require 'client.php';

$msgBody = <<<EOT
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz
abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyza
EOT;

$client = $client
    ->with(DeclareQueue::of('bench_queue'))
    ->with(DeclareExchange::of('bench_exchange', Type::direct))
    ->with(Bind::of('bench_exchange', 'bench_queue'))
    ->with(Publish::many(
        Sequence::lazyStartingWith(...\range(1, isset($argv[1]) ? (int) $argv[1] : 1))
            ->map(static fn() => Str::of($msgBody))
            ->map(Message::of(...))
            ->map(Model\Publish::a(...))
            ->add(Model\Publish::a(Message::of(Str::of('quit'))))
            ->map(static fn($publish) => $publish->to('bench_exchange')),
    ));

$time = microtime(true);

$_ = $client
    ->run(null)
    ->match(
        static fn() => null,
        static fn($failure) => throw new \RuntimeException($failure::class),
    );

echo microtime(true) - $time, "\n";
