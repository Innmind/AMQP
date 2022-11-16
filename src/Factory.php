<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Socket\Internet\Transport as Socket;
use Innmind\Url\Url;
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

final class Factory
{
    private OperatingSystem $os;

    private function __construct(OperatingSystem $os)
    {
        $this->os = $os;
    }

    public static function of(OperatingSystem $os): self
    {
        return new self($os);
    }

    /**
     * @no-named-arguments
     *
     * @param callable(Transport\Connection): Transport\Connection $decorators
     */
    public function make(
        Socket $transport,
        Url $server,
        ElapsedPeriod $timeout,
        callable ...$decorators,
    ): Client {
        $decorators = Sequence::of(...$decorators);

        return new Client\Client(
            function() use ($transport, $server, $timeout, $decorators): Transport\Connection {
                $connection = Transport\Connection\Connection::of(
                    $transport,
                    $server,
                    new Transport\Protocol(
                        $this->os->clock(),
                        new Transport\Protocol\ArgumentTranslator\ValueTranslator,
                    ),
                    $timeout,
                    $this->os->clock(),
                    $this->os->remote(),
                    $this->os->sockets(),
                );

                /** @psalm-suppress MixedArgumentTypeCoercion */
                return $decorators
                    ->reduce(
                        $connection,
                        static fn(Maybe $connection, $decorate): Maybe => $connection->map($decorate),
                    )->match(
                        static fn(Transport\Connection $connection) => $connection,
                        static fn() => throw new \RuntimeException,
                    );
            },
            $this->os->process(),
        );
    }
}
