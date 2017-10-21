<?php
declare(strict_types = 1);

use Innmind\AMQP\Model\{
    Queue\Declaration,
    Basic\Publish,
    Basic\Message\Generic
};
use Innmind\Immutable\Str;

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

$time = microtime(true);

$max = isset($argv[1]) ? (int) $argv[1] : 1;

// Publishes $max messages using $msgBody as the content.
for ($i = 0; $i < $max; $i++) {
    $channel = $client->channel();
    $queue = $channel
        ->queue()
        ->declare(
            Declaration::autoDelete()
                ->exclusive()
        )
        ->name();
    $channel
        ->basic()
        ->publish(
            (new Publish(new Generic(new Str($msgBody))))
                ->withRoutingKey($queue)
        );
    //$channel->close(); not executed as only one channel allowed per process
}

echo microtime(true) - $time, "\n";
$client->close();
