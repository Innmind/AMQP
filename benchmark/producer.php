<?php
declare(strict_types = 1);

use Innmind\AMQP\Model\{
    Queue\Declaration as Queue,
    Queue\Binding,
    Exchange\Declaration as Exchange,
    Exchange\Type,
    Basic\Publish,
    Basic\Message\Generic
};
use Innmind\Immutable\Str;

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
        Exchange::temporary('bench_exchange', Type::direct)
    );
$channel
    ->queue()
    ->bind(
        new Binding('bench_exchange', 'bench_queue')
    );

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
$message = (new Publish(new Generic(Str::of($msgBody))))
    ->to('bench_exchange');

$time = microtime(true);
$max = isset($argv[1]) ? (int) $argv[1] : 1;

// Publishes $max messages using $msgBody as the content.
for ($i = 0; $i < $max; $i++) {
    $channel
        ->basic()
        ->publish($message);
}

echo microtime(true) - $time, "\n";

$channel
    ->basic()
    ->publish(
        (new Publish(new Generic(Str::of('quit'))))->to('bench_exchange')
    );

$client->close();
