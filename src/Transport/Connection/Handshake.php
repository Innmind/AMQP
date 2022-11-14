<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Value,
    Model\Connection\SecureOk,
    Model\Connection\TuneOk,
    Model\Connection\MaxChannels,
    Model\Connection\MaxFrameSize,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Url\Authority;

final class Handshake
{
    private Authority $authority;

    public function __construct(Authority $authority)
    {
        $this->authority = $authority;
    }

    public function __invoke(Connection $connection): Connection
    {
        $frame = $connection->wait(Method::connectionSecure, Method::connectionTune);

        if ($frame->is(Method::connectionSecure)) {
            $connection->send($connection->protocol()->connection()->secureOk(
                new SecureOk(
                    $this->authority->userInformation()->user(),
                    $this->authority->userInformation()->password(),
                ),
            ));
            $frame = $connection->wait(Method::connectionTune);
        }

        /** @var Value\UnsignedShortInteger */
        $maxChannels = $frame->values()->get(0)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $maxChannels = new MaxChannels($maxChannels->original());
        /** @var Value\UnsignedLongInteger */
        $maxFrameSize = $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $maxFrameSize = new MaxFrameSize($maxFrameSize->original());
        /** @var Value\UnsignedShortInteger */
        $heartbeat = $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $threshold = new ElapsedPeriod($heartbeat->original());
        $connection = $connection->tune(
            $maxChannels,
            $maxFrameSize,
            $threshold,
        );
        $connection->send($connection->protocol()->connection()->tuneOk(
            new TuneOk(
                $maxChannels,
                $maxFrameSize,
                $threshold,
            ),
        ));

        return $connection;
    }
}
