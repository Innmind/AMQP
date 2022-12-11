<?php
declare(strict_types = 1);

require_once  __DIR__.'/../vendor/autoload.php';

use Innmind\AMQP\{
    Factory,
    Transport\Connection,
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Url\Url;
use Innmind\OperatingSystem\Factory as OSFactory;

$os = OSFactory::build();

return Factory::of($os)
    ->make(
        Transport::tcp(),
        Url::of('//guest:guest@localhost:5672/'),
        new ElapsedPeriod(1000),
    )
    ->listenSignals($os->process());
