<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\Socket\Internet\Transport as Socket;
use Innmind\Url\Url;
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
};
use Innmind\CLI\Command as CLICommand;
use Innmind\OperatingSystem\{
    CurrentProcess,
    CurrentProcess\Signals,
    Remote,
    Sockets,
};
use Innmind\Immutable\{
    Set,
    Map,
};
use function Innmind\Immutable\unwrap;
use Psr\Log\LoggerInterface;

function bootstrap(LoggerInterface $logger = null): array
{
    return [
        'client' => [
            'basic' => static function(
                Socket $transport,
                Url $server,
                ElapsedPeriod $timeout,
                Clock $clock,
                CurrentProcess $process,
                Remote $remote,
                Sockets $sockets
            ) use (
                $logger
            ): Client {
                $connection = new Transport\Connection\Lazy(
                    $transport,
                    $server,
                    new Transport\Protocol\v091\Protocol(
                        new Transport\Protocol\ArgumentTranslator\ValueTranslator
                    ),
                    $timeout,
                    $clock,
                    $remote,
                    $sockets,
                );

                if ($logger instanceof LoggerInterface) {
                    $connection = new Transport\Connection\Logger($connection, $logger);
                }

                return new Client\Client($connection, $process);
            },
            'fluent' => static function(Client $client): Client {
                return new Client\Fluent($client);
            },
            'logger' => static function(Client $client) use ($logger): Client {
                return new Client\Logger($client, $logger);
            },
            'signal_aware' => static function(Client $client, Signals $signals): Client {
                return new Client\SignalAware($client, $signals);
            },
            'auto_declare' => static function(Set $exchanges, Set $queues, Set $bindings): callable {
                return static function(Client $client) use ($exchanges, $queues, $bindings): Client {
                    return new Client\AutoDeclare($client, $exchanges, $queues, $bindings);
                };
            },
        ],
        'command' => [
            'purge' => static function(Client $client): CLICommand {
                return new Command\Purge($client);
            },
            'get' => static function(Map $consumers): callable {
                return static function(Client $client) use ($consumers): CLICommand {
                    return new Command\Get($client, new Consumers($consumers));
                };
            },
            'consume' => static function(Map $consumers): callable {
                return static function(Client $client) use ($consumers): CLICommand {
                    return new Command\Consume($client, new Consumers($consumers));
                };
            },
        ],
        'producers' => static function(Set $exchanges): callable {
            return static function(Client $client) use ($exchanges): Producers {
                return Producers::fromDeclarations($client, ...unwrap($exchanges));
            };
        },
    ];
}
