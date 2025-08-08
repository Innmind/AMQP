<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Model\Connection\SecureOk,
    Model\Connection\MaxChannels,
    Model\Connection\MaxFrameSize,
    Failure,
};
use Innmind\TimeContinuum\Period;
use Innmind\Url\Authority;
use Innmind\Immutable\{
    Maybe,
    Either,
    Predicate\Instance,
};

/**
 * @internal
 */
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
        return $connection
            ->wait(Method::connectionSecure, Method::connectionTune)
            ->flatMap(fn($received) => match ($received->is(Method::connectionSecure)) {
                true => $this->secure($connection),
                false => $this->maybeTune($connection, $received->frame()),
            })
            ->maybe();
    }

    /**
     * @return Either<Failure, Connection>
     */
    private function secure(Connection $connection): Either
    {
        return $connection
            ->request(
                fn($protocol) => $protocol->connection()->secureOk(
                    SecureOk::of(
                        $this->authority->userInformation()->user(),
                        $this->authority->userInformation()->password(),
                    ),
                ),
                Method::connectionTune,
            )
            ->flatMap(fn($frame) => $this->maybeTune($connection, $frame));
    }

    /**
     * @return Either<Failure, Connection>
     */
    private function maybeTune(Connection $connection, Frame $frame): Either
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
            ->map(Period::millisecond(...))
            ->map(static fn($period) => $period->asElapsedPeriod());

        return Maybe::all($maxChannels, $maxFrameSize, $heartbeat)
            ->flatMap($connection->tune(...))
            ->either()
            ->leftMap(static fn() => Failure::toOpenConnection());
    }
}
