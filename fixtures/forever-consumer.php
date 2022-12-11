<?php
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\AMQP\{
    Factory,
    Command\DeclareQueue,
    Command\Consume,
};
use Innmind\OperatingSystem\Factory as OSFactory;
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Url\Url;

$os = OSFactory::build();
$success = Factory::of($os)
    ->make(
        Transport::tcp(),
        Url::of('//guest:guest@localhost:5672/'),
        new ElapsedPeriod(1000),
    )
    ->listenSignals($os->process())
    ->with(DeclareQueue::of('always-empty'))
    ->with(Consume::of('always-empty'))
    ->run(null)
    ->match(
        static fn() => true,
        static fn() => false,
    );

if ($success) {
    exit;
}

exit(1);
