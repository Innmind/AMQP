<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use Innmind\AMQP\Client\{
    Client,
    SignalAware
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
    }
}