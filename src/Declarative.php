<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Channel\Close as CloseChannel,
};
use Innmind\Immutable\{
    Either,
    Maybe,
    SideEffect,
};

/**
 * @template I
 * @template O
 */
final class Declarative
{
    /** @var Maybe<Command<I, O>> */
    private Maybe $command;
    /** @var callable(): Maybe<Connection> */
    private $load;

    /**
     * @param Maybe<Command<I, O>> $command
     * @param callable(): Maybe<Connection> $load
     */
    private function __construct(Maybe $command, callable $load)
    {
        $this->command = $command;
        $this->load = $load;
    }

    /**
     * @param callable(): Maybe<Connection> $load
     *
     * @return self<null, null>
     */
    public static function of(callable $load): self
    {
        /** @var Maybe<Command<null, null>> */
        $command = Maybe::nothing();

        return new self($command, $load);
    }

    /**
     * @template CO
     *
     * @param Command<I, CO> $command
     *
     * @return self<I, CO>
     */
    public function with(Command $command): self
    {
        return new self(
            $this
                ->command
                ->map(static fn($previous) => new Command\Pipe($previous, $command))
                ->otherwise(static fn() => Maybe::just($command)),
            $this->load,
        );
    }

    /**
     * @return Either<Failure, O>
     */
    public function run(): Either
    {
        return $this->command->match(
            fn($command) => $this
                ->openChannel()
                ->flatMap(function($in) use ($command) {
                    [$connection, $channel] = $in;

                    return $command($connection, $channel, null)->flatMap(function($out) use ($channel) {
                        [$connection, $state] = $out;

                        return $this
                            ->close($connection, $channel)
                            ->map(static fn() => $state);
                    });
                }),
            static fn() => Either::right(null),
        );
    }

    /**
     * @return Either<Failure, array{Connection, Channel}>
     */
    private function openChannel(): Either
    {
        // Since the connection is never shared between objects then there is no
        // need to have a dynamic channel number as there will ALWAYS be one
        // channel per connection
        $channel = new Channel(1);

        /**
         * @psalm-suppress InvalidArgument Due to the last flatMap psalm is not capable of correctly understanding the returned Either
         * @var Either<Failure, array{Connection, Channel}>
         */
        return ($this->load)()
            ->either()
            ->leftMap(static fn() => Failure::toOpenConnection)
            ->map(static fn($connection) => $connection->send(
                static fn($protocol) => $protocol->channel()->open($channel)
            ))
            ->map(static fn($continuation) => $continuation->wait(Method::channelOpenOk))
            ->flatMap(static fn($continuation) => $continuation->match(
                static fn($connection) => Either::right([$connection, $channel]),
                static fn($connection) => Either::right([$connection, $channel]),
                static fn() => Either::left(Failure::toOpenChannel),
            ));
    }

    /**
     * @return Either<Failure, SideEffect>
     */
    private function close(Connection $connection, Channel $channel): Either
    {
        /** @var Either<Failure, SideEffect> */
        return $connection
            ->send(static fn($protocol) => $protocol->channel()->close(
                $channel,
                CloseChannel::demand(),
            ))
            ->wait(Method::channelCloseOk)
            ->match(
                static fn($connection) => Either::right($connection->close())->map(static fn() => new SideEffect),
                static fn($connection) => Either::right($connection->close())->map(static fn() => new SideEffect),
                static fn() => Either::left(Failure::toCloseChannel),
            );
    }
}
