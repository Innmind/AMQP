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
    CurrentProcess\Signals,
    Remote,
    Sockets,
};
use Innmind\TimeContinuum\{
    Clock,
    Earth\ElapsedPeriod,
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
        $services = bootstrap($this->createMock(LoggerInterface::class));

        $fluent = $services['client']['fluent'];
        $logger = $services['client']['logger'];
        $signalAware = $services['client']['signal_aware'];
        $autoDeclare = $services['client']['auto_declare'];

        $basic = $services['client']['basic'](
            Transport::tcp(),
            Url::of('amqp://localhost'),
            new ElapsedPeriod(60000),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
            $this->createMock(Remote::class),
            $this->createMock(Sockets::class),
        );

        $this->assertIsCallable($services['client']['basic']);
        $this->assertInstanceOf(Client::class, $basic);
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
            $fluent($basic)
        );
        $this->assertInstanceOf(
            Logger::class,
            $logger($basic)
        );
        $this->assertInstanceOf(
            SignalAware::class,
            $signalAware($basic, $this->createMock(Signals::class))
        );
        $this->assertInstanceOf(
            AutoDeclare::class,
            $autoDeclare(
                Set::of(Exchange::class),
                Set::of(Queue::class),
                Set::of(Binding::class)
            )($basic)
        );

        $purge = $services['command']['purge'];
        $get = $services['command']['get'];
        $consume = $services['command']['consume'];
        $this->assertIsCallable($purge);
        $this->assertIsCallable($get);
        $this->assertIsCallable($consume);
        $this->assertInstanceOf(
            Purge::class,
            $purge($basic)
        );
        $consumers = Map::of('string', 'callable');
        $this->assertIsCallable($get($consumers));
        $this->assertIsCallable($consume($consumers));
        $this->assertInstanceOf(
            Get::class,
            $get($consumers)($basic)
        );
        $this->assertInstanceOf(
            Consume::class,
            $consume($consumers)($basic)
        );

        $producers = $services['producers'];
        $this->assertIsCallable($producers);
        $this->assertIsCallable($producers(Set::of(Exchange::class)));
        $this->assertInstanceOf(
            Producers::class,
            $producers(Set::of(Exchange::class))($basic)
        );
    }
}
