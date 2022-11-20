<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use Innmind\AMQP\{
    Declarative,
    Transport\Connection,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Protocol,
    Command\DeclareExchange,
    Command\DeleteExchange,
    Model\Exchange\Type,
};
use Innmind\Socket\Internet\Transport;
use Innmind\OperatingSystem\Factory;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class DeclarativeTest extends TestCase
{
    private Declarative $client;

    public function setUp(): void
    {
        $os = Factory::build();

        $this->client = Declarative::of(
            fn() => $this->connection = Connection::open(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol($os->clock(), new ValueTranslator),
                new ElapsedPeriod(1000),
                $os->clock(),
                $os->remote(),
                $os->sockets(),
            )->match(
                static fn($connection) => $connection,
                static fn() => throw new \RuntimeException,
            ),
        );
    }

    public function testDeclareExchange()
    {
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeleteExchange::of('foo'))
            ->run()
            ->match(
                static fn($state) => $state,
                static fn($error) => $error,
            );

        $this->assertNull($result);
    }
}
