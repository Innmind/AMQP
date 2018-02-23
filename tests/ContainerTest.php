<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use Innmind\AMQP\{
    Client\Client,
    Client\SignalAware,
    Command\Get,
    Command\Purge,
    Command\Consume,
};
use Innmind\Compose\{
    ContainerBuilder\ContainerBuilder,
    Loader\Yaml
};
use Innmind\Url\{
    Path,
    Url
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod
};
use Innmind\Immutable\Map;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testServices()
    {
        $container = (new ContainerBuilder(new Yaml))(
            new Path('container.yml'),
            (new Map('string', 'mixed'))
                ->put('transport', Transport::tcp())
                ->put('server', Url::fromString('amqp://localhost'))
                ->put('timeout', new ElapsedPeriod(60000))
                ->put('clock', $this->createMock(TimeContinuumInterface::class))
                ->put('logger', $this->createMock(LoggerInterface::class))
        );

        $this->assertInstanceOf(Client::class, $container->get('basic'));
        $this->assertInstanceOf(SignalAware::class, $container->get('client'));
        $this->assertInstanceOf(Get::class, $container->get('getCommand'));
        $this->assertInstanceOf(Purge::class, $container->get('purgeCommand'));
        $this->assertInstanceOf(Consume::class, $container->get('consumeCommand'));
    }
}
