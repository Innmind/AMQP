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
 * @template E
 */
final class Declarative
{
    /** @var Maybe<Command<I, O, E>> */
    private Maybe $command;
    /** @var callable(): Connection */
    private $load;

    /**
     * @param Maybe<Command<I, O, E>> $command
     * @param callable(): Connection $load
     */
    private function __construct(Maybe $command, callable $load)
    {
        $this->command = $command;
        $this->load = $load;
    }

    /**
     * @param callable(): Connection $load
     *
     * @return self<null, null, \Error>
     */
    public static function of(callable $load): self
    {
        /** @var Maybe<Command<null, null, \Error>> */
        $command = Maybe::nothing();

        return new self($command, $load);
    }

    /**
     * @template CO
     * @template CE
     *
     * @param Command<I, CO, CE> $command
     *
     * @return self<I, CO, E|CE>
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
     * @return Either<E, O>
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
     * @return Either<\RuntimeException, array{Connection, Channel}>
     */
    private function openChannel(): Either
    {
        // Since the connection is never shared between objects then there is no
        // need to have a dynamic channel number as there will ALWAYS be one
        // channel per connection
        $channel = new Channel(1);

        return ($this->load)()
            ->send(static fn($protocol) => $protocol->channel()->open($channel))
            ->wait(Method::channelOpenOk)
            ->match(
                static fn($connection) => Either::right([$connection, $channel]),
                static fn($connection) => Either::right([$connection, $channel]),
                static fn() => Either::left(new \RuntimeException),
            );
    }

    /**
     * @return Either<\Error, SideEffect>
     */
    private function close(Connection $connection, Channel $channel): Either
    {
        return $connection
            ->send(static fn($protocol) => $protocol->channel()->close(
                $channel,
                CloseChannel::demand(),
            ))
            ->wait(Method::channelCloseOk)
            ->match(
                static fn($connection) => Either::right($connection->close())->map(static fn() => new SideEffect),
                static fn($connection) => Either::right($connection->close())->map(static fn() => new SideEffect),
                static fn() => Either::left(new \RuntimeException),
            );
    }
}
