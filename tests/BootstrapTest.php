<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use function Innmind\AMQP\bootstrap;
use Innmind\AMQP\{
    Client\Client,
    Client\Fluent,
    Client\Logger,
    Client\SignalAware,
    Client\AutoDeclare,
    Model\Exchange\Declaration as Exchange,
    Model\Queue\Declaration as Queue,
    Model\Queue\Binding,
    Command\Purge,
    Command\Get,
    Command\Consume,
    Producers,
};
use Innmind\Socket\Internet\Transport;
use Innmind\Url\Url;
use Innmind\OperatingSystem\{
    CurrentProcess,
    Remote,
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod,
};
use Innmind\Immutable\{
    Set,
    Map,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $services = bootstrap(
            Transport::tcp(),
            Url::fromString('amqp://localhost'),
            new ElapsedPeriod(60000),
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(CurrentProcess::class),
            $this->createMock(Remote::class),
            $this->createMock(LoggerInterface::class)
        );

        $fluent = $services['client']['fluent'];
        $logger = $services['client']['logger'];
        $signalAware = $services['client']['signal_aware'];
        $autoDeclare = $services['client']['auto_declare'];
        $this->assertInstanceOf(Client::class, $services['client']['basic']);
        $this->assertIsCallable($fluent);
        $this->assertIsCallable($logger);
        $this->assertIsCallable($signalAware);
        $this->assertIsCallable($autoDeclare);
        $this->assertIsCallable($autoDeclare(
            Set::of(Exchange::class),
            Set::of(Queue::class),
            Set::of(Binding::class)
        ));
        $this->assertInstanceOf(
            Fluent::class,
            $fluent($services['client']['basic'])
        );
        $this->assertInstanceOf(
            Logger::class,
            $logger($services['client']['basic'])
        );
        $this->assertInstanceOf(
            SignalAware::class,
            $signalAware($services['client']['basic'])
        );
        $this->assertInstanceOf(
            AutoDeclare::class,
            $autoDeclare(
                Set::of(Exchange::class),
                Set::of(Queue::class),
                Set::of(Binding::class)
            )($services['client']['basic'])
        );

        $purge = $services['command']['purge'];
        $get = $services['command']['get'];
        $consume = $services['command']['consume'];
        $this->assertIsCallable($purge);
        $this->assertIsCallable($get);
        $this->assertIsCallable($consume);
        $this->assertInstanceOf(
            Purge::class,
            $purge($services['client']['basic'])
        );
        $consumers = new Map('string', 'callable');
        $this->assertIsCallable($get($consumers));
        $this->assertIsCallable($consume($consumers));
        $this->assertInstanceOf(
            Get::class,
            $get($consumers)($services['client']['basic'])
        );
        $this->assertInstanceOf(
            Consume::class,
            $consume($consumers)($services['client']['basic'])
        );

        $producers = $services['producers'];
        $this->assertIsCallable($producers);
        $this->assertIsCallable($producers(Set::of(Exchange::class)));
        $this->assertInstanceOf(
            Producers::class,
            $producers(Set::of(Exchange::class))($services['client']['basic'])
        );
    }
}
