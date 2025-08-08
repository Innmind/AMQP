<?php
declare(strict_types = 1);

require_once  __DIR__.'/../vendor/autoload.php';

use Innmind\AMQP\{
    Factory,
    Transport\Connection,
};
use Innmind\IO\Sockets\Internet\Transport;
use Innmind\TimeContinuum\Period;
use Innmind\Url\Url;
use Innmind\OperatingSystem\Factory as OSFactory;

$os = OSFactory::build();

return Factory::of($os)
    ->make(
        Transport::tcp(),
        Url::of('//guest:guest@localhost:5672/'),
        Period::second(1),
    )
    ->listenSignals($os->process());
