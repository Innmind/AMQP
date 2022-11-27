<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame,
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

    /**
     * @return Maybe<Connection>
     */
    public function __invoke(Connection $connection): Maybe
    {
        $frame = $connection->wait(Method::connectionSecure, Method::connectionTune)->match(
            static fn($received) => $received->frame(),
            static fn() => throw new \RuntimeException,
        );

        if ($frame->is(Method::connectionSecure)) {
            return $connection
                ->send(fn($protocol) => $protocol->connection()->secureOk(
                    SecureOk::of(
                        $this->authority->userInformation()->user(),
                        $this->authority->userInformation()->password(),
                    ),
                ))
                ->wait(Method::connectionTune)
                ->then(
                    $this->maybeTune(...),
                    static fn($connection) => $connection,
                );
        }

        return $this->maybeTune($connection, $frame);
    }

    /**
     * @return Maybe<Connection>
     */
    private function maybeTune(Connection $connection, Frame $frame): Maybe
    {
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
            ->flatMap(fn(MaxChannels $maxChannels, MaxFrameSize $maxFrameSize, ElapsedPeriod $heartbeat) => $this->tune(
                $connection,
                $maxChannels,
                $maxFrameSize,
                $heartbeat,
            ));
    }

    /**
     * @return Maybe<Connection>
     */
    private function tune(
        Connection $connection,
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        ElapsedPeriod $heartbeat,
    ): Maybe {
        return $connection
            ->tune($maxChannels, $maxFrameSize, $heartbeat)
            ->send(static fn($protocol) => $protocol->connection()->tuneOk(
                TuneOk::of(
                    $maxChannels,
                    $maxFrameSize,
                    $heartbeat,
                ),
            ))
            ->either()
            ->maybe();
    }
}
