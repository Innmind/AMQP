<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Queue;

use Innmind\AMQP\{
    Client\Channel\Queue\Queue,
    Client\Channel\Queue as QueueInterface,
    Transport\Connection\Connection,
    Transport\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Queue\Declaration,
    Model\Queue\DeclareOk,
    Model\Queue\Purge,
    Model\Queue\PurgeOk,
    Model\Queue\Deletion,
    Model\Queue\DeleteOk,
    Model\Queue\Binding,
    Model\Queue\Unbinding,
    Model\Channel\Close,
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    Clock,
};
use Innmind\Url\Url;
use Innmind\OperatingSystem\{
    Remote,
    Sockets,
};
use Innmind\Server\Control\Server;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    private $queue;
    private $conncetion;

    public function setUp(): void
    {
        $this->queue = new Queue(
            $this->connection = new Connection(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            ),
            new Channel(1),
        );
        $this->connection->send(
            $this->connection->protocol()->channel()->open(new Channel(1)),
        );
        $this->connection->wait(Method::channelOpenOk);
    }

    public function tearDown(): void
    {
        $this->connection->send(
            $this->connection->protocol()->channel()->close(
                new Channel(1),
                new Close,
            ),
        );
        $this->connection->wait(Method::channelCloseOk);
        $this->connection->close();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(QueueInterface::class, $this->queue);
    }

    public function testDeclare()
    {
        $this->assertNull($this->queue->declare(
            Declaration::durable()->dontWait(),
        ));
        $response = $this->queue->declare(
            Declaration::durable(),
        );
        $this->assertInstanceOf(DeclareOk::class, $response);
        $this->assertNotEmpty($response->name());
    }

    public function testBind()
    {
        $queue = $this
            ->queue
            ->declare(
                Declaration::durable(),
            )
            ->name();

        $this->assertNull(
            $this->queue->bind(
                (new Binding('amq.direct', $queue, 'foo'))->dontWait(),
            ),
        );
        $this->assertNull(
            $this->queue->bind(
                new Binding('amq.direct', $queue, 'bar'),
            ),
        );
    }

    public function testUnbind()
    {
        $queue = $this
            ->queue
            ->declare(
                Declaration::durable(),
            )
            ->name();

        $this->assertNull(
            $this->queue->unbind(
                new Unbinding('amq.direct', $queue, 'bar'),
            ),
        );
    }

    public function testPurge()
    {
        $queue = $this
            ->queue
            ->declare(
                Declaration::durable(),
            )
            ->name();

        $this->assertNull($this->queue->purge(
            (new Purge($queue))->dontWait(),
        ));

        $response = $this->queue->purge(new Purge($queue));
        $this->assertInstanceOf(PurgeOk::class, $response);
    }

    public function testDelete()
    {
        $queue = $this
            ->queue
            ->declare(
                Declaration::durable(),
            )
            ->name();

        $this->assertNull($this->queue->delete(
            (new Deletion($queue))->dontWait(),
        ));

        $response = $this->queue->delete(new Deletion($queue));
        $this->assertInstanceOf(DeleteOk::class, $response);
    }
}
