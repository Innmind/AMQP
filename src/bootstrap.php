<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Model\{
    Exchange,
    Queue,
    Basic\Message,
};
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
use Psr\Log\LoggerInterface;

/**
 * @return array{client: array{basic: callable(Socket, Url, ElapsedPeriod, Clock, CurrentProcess, Remote, Sockets, LoggerInterface = null): Client, fluent: callable(Client): Client, logger: callable(Client, LoggerInterface): Client, signal_aware: callable(Client, Signals): Client, auto_declare: callable(Set<Exchange\Declaration>, Set<Queue\Declaration>, Set<Queue\Binding>): (callable(Client): Client)}, command: array{purge: callable(Client): CLICommand, get: callable(Map<string, callable(Message, bool, string, string): void>): (callable(Client): CLICommand), consume: callable(Map<string, callable(Message, bool, string, string): void>): (callable(Client): CLICommand)}, producers: callable(Set<Exchange\Declaration>): (callable(Client): Producers)}
 */
function bootstrap(): array
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
                Sockets $sockets,
                LoggerInterface $logger = null,
            ): Client {
                $load = static function() use ($transport, $server, $timeout, $clock, $remote, $sockets, $logger): Transport\Connection {
                    $connection = new Transport\Connection\Lazy(
                        $transport,
                        $server,
                        new Transport\Protocol(
                            $clock,
                            new Transport\Protocol\ArgumentTranslator\ValueTranslator,
                        ),
                        $timeout,
                        $clock,
                        $remote,
                        $sockets,
                    );

                    if ($logger instanceof LoggerInterface) {
                        $connection = new Transport\Connection\Logger($connection, $logger);
                    }

                    return $connection;
                };

                return new Client\Client($load, $process);
            },
            'fluent' => static function(Client $client): Client {
                return new Client\Fluent($client);
            },
            'logger' => static function(Client $client, LoggerInterface $logger): Client {
                return new Client\Logger($client, $logger);
            },
            'signal_aware' => static function(Client $client, Signals $signals): Client {
                return new Client\SignalAware($client, $signals);
            },
            'auto_declare' => static function(Set $exchanges, Set $queues, Set $bindings): callable {
                /**
                 * @var Set<Exchange\Declaration> $exchanges
                 * @var Set<Queue\Declaration> $queues
                 * @var Set<Queue\Binding> $bindings
                 */
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
                /** @var Map<string, callable(Message, bool, string, string): void> $consumers */
                return static function(Client $client) use ($consumers): CLICommand {
                    return new Command\Get($client, new Consumers($consumers));
                };
            },
            'consume' => static function(Map $consumers): callable {
                /** @var Map<string, callable(Message, bool, string, string): void> $consumers */
                return static function(Client $client) use ($consumers): CLICommand {
                    return new Command\Consume($client, new Consumers($consumers));
                };
            },
        ],
        'producers' => static function(Set $exchanges): callable {
            /** @var Set<Exchange\Declaration> $exchanges */
            return static function(Client $client) use ($exchanges): Producers {
                return Producers::of($client, ...$exchanges->toList());
            };
        },
    ];
}
