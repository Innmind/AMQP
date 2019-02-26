<?php
declare(strict_types = 1);

require_once  __DIR__.'/../vendor/autoload.php';

use Innmind\AMQP\{
    Client\SignalAware,
    Client\Client,
    Transport\Connection\Connection,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Protocol\v091\Protocol,
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Url\Url;
use Innmind\OperatingSystem\Factory;

$os = Factory::build();

return new SignalAware(
    new Client(
        new Connection(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5672/'),
            new Protocol(new ValueTranslator),
            new ElapsedPeriod(1000),
            $os->clock(),
            $os->remote()
        ),
        $os->process()
    ),
    $os->process()->signals()
);
