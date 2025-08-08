<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\IO\Sockets\Internet\Transport as Socket;
use Innmind\Url\Url;
use Innmind\TimeContinuum\ElapsedPeriod;

final class Factory
{
    private OperatingSystem $os;

    private function __construct(OperatingSystem $os)
    {
        $this->os = $os;
    }

    #[\NoDiscard]
    public static function of(OperatingSystem $os): self
    {
        return new self($os);
    }

    #[\NoDiscard]
    public function make(
        Socket $transport,
        Url $server,
        ElapsedPeriod $timeout,
    ): Client {
        return Client::of(
            fn() => Transport\Connection::open(
                $transport,
                $server,
                new Transport\Protocol(
                    $this->os->clock(),
                    new Transport\Protocol\ArgumentTranslator,
                ),
                $timeout,
                $this->os->clock(),
                $this->os->remote(),
            ),
            $this->os->filesystem(),
        );
    }
}
