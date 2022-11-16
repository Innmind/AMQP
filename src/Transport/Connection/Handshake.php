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
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

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
                SecureOk::of(
                    $this->authority->userInformation()->user(),
                    $this->authority->userInformation()->password(),
                ),
            ));
            $frame = $connection->wait(Method::connectionTune);
        }

        $maxChannels = $frame
            ->values()
            ->first()
            ->keep(Instance::of(Value\UnsignedShortInteger::class))
            ->map(static fn($value) => $value->original())
            ->map(MaxChannels::of(...));
        $maxFrameSize = $frame
            ->values()
            ->get(1)
            ->keep(Instance::of(Value\UnsignedLongInteger::class))
            ->map(static fn($value) => $value->original())
            ->map(MaxFrameSize::of(...));
        $heartbeat = $frame
            ->values()
            ->get(2)
            ->keep(Instance::of(Value\UnsignedShortInteger::class))
            ->map(static fn($value) => $value->original())
            ->map(ElapsedPeriod::of(...));

        return Maybe::all($maxChannels, $maxFrameSize, $heartbeat)
            ->map(fn(MaxChannels $maxChannels, MaxFrameSize $maxFrameSize, ElapsedPeriod $heartbeat) => $this->tune(
                $connection,
                $maxChannels,
                $maxFrameSize,
                $heartbeat,
            ))
            ->match(
                static fn($connection) => $connection,
                static fn() => throw new \LogicException,
            );
    }

    private function tune(
        Connection $connection,
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        ElapsedPeriod $heartbeat,
    ): Connection {
        $connection = $connection->tune($maxChannels, $maxFrameSize, $heartbeat);
        $connection->send($connection->protocol()->connection()->tuneOk(
            TuneOk::of(
                $maxChannels,
                $maxFrameSize,
                $heartbeat,
            ),
        ));

        return $connection;
    }
}
