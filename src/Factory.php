<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\IO\Sockets\Internet\Transport as Socket;
use Innmind\Url\Url;
use Innmind\TimeContinuum\Period;

final class Factory
{
    private function __construct(private OperatingSystem $os)
    {
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
        Period $timeout,
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
