<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\Socket\Internet\Transport as Socket;
use Innmind\Url\UrlInterface;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod,
};
use Innmind\CLI\Command as CLICommand;
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
};
use Psr\Log\LoggerInterface;

function bootstrap(
    Socket $transport,
    UrlInterface $server,
    ElapsedPeriod $timeout,
    TimeContinuumInterface $clock,
    LoggerInterface $logger = null,
    SetInterface $protocols = null,
    SetInterface $argumentTranslators = null
): array {
    $argumentTranslators = $argumentTranslators ?? Set::of(
        Transport\Protocol\ArgumentTranslator::class,
        new Transport\Protocol\ArgumentTranslator\ValueTranslator
    );
    $protocols = $protocols ?? Set::of(
        Transport\Protocol::class,
        new Transport\Protocol\v091\Protocol(
            new Transport\Protocol\ArgumentTranslator\Delegate(...$argumentTranslators)
        )
    );

    $connection = new Transport\Connection\Lazy(
        $transport,
        $server,
        new Transport\Protocol\Delegate(...$protocols),
        $timeout,
        $clock
    );

    if ($logger instanceof LoggerInterface) {
        $connection = new Transport\Connection\Logger($connection, $logger);
    }

    return [
        'client' => [
            'basic' => new Client\Client($connection),
            'fluent' => static function(Client $client): Client {
                return new Client\Fluent($client);
            },
            'logger' => static function(Client $client) use ($logger): Client {
                return new Client\Logger($client, $logger);
            },
            'signal_aware' => static function(Client $client): Client {
                return new Client\SignalAware($client);
            },
            'auto_declare' => static function(SetInterface $exchanges, SetInterface $queues, SetInterface $bindings): callable {
                return static function(Client $client) use ($exchanges, $queues, $bindings): Client {
                    return new Client\AutoDeclare($client, $exchanges, $queues, $bindings);
                };
            },
        ],
        'command' => [
            'purge' => static function(Client $client): CLICommand {
                return new Command\Purge($client);
            },
            'get' => static function(MapInterface $consumers): callable {
                return static function(Client $client) use ($consumers): CLICommand {
                    return new Command\Get($client, new Consumers($consumers));
                };
            },
            'consume' => static function(MapInterface $consumers): callable {
                return static function(Client $client) use ($consumers): CLICommand {
                    return new Command\Consume($client, new Consumers($consumers));
                };
            },
        ],
        'producers' => static function(SetInterface $exchanges): callable {
            return static function(Client $client) use ($exchanges): Producers {
                return Producers::fromDeclarations($client, ...$exchanges);
            };
        },
    ];
}
