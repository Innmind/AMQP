<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Connection\Start,
    Transport\Connection\Handshake,
    Transport\Connection\OpenVHost,
    Transport\Connection\Heartbeat,
    Transport\Connection\FrameReader,
    Transport\Connection\SignalListener,
    Transport\Frame\Channel,
    Transport\Frame\Type,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Model\Connection\Close,
    Model\Connection\MaxChannels,
    Model\Connection\MaxFrameSize,
    Failure,
    Exception\FrameChannelExceedAllowedChannelNumber,
    Exception\FrameExceedAllowedSize,
};
use Innmind\OperatingSystem\CurrentProcess\Signals;
use Innmind\Socket\{
    Internet\Transport,
    Client as Socket,
};
use Innmind\IO\{
    IO,
    Sockets\Client,
    Readable\Frame as IOFrame,
};
use Innmind\Url\Url;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    Clock,
};
use Innmind\OperatingSystem\{
    Remote,
    Sockets,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
    Sequence,
    SideEffect,
    Predicate\Instance,
};

/**
 * @internal You should use the Client instead
 */
final class Connection
{
    private Protocol $protocol;
    /** @var Client<Socket> */
    private Client $socket;
    /** @var IOFrame<Frame> */
    private IOFrame $frame;
    private MaxChannels $maxChannels;
    private MaxFrameSize $maxFrameSize;
    private Heartbeat $heartbeat;
    private SignalListener $signals;

    /**
     * @param Client<Socket> $socket
     * @param IOFrame<Frame> $frame
     */
    private function __construct(
        Protocol $protocol,
        Heartbeat $heartbeat,
        Client $socket,
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        IOFrame $frame,
        SignalListener $signals,
    ) {
        $this->protocol = $protocol;
        $this->socket = $socket;
        $this->frame = $frame;
        $this->maxChannels = $maxChannels;
        $this->maxFrameSize = $maxFrameSize;
        $this->heartbeat = $heartbeat;
        $this->signals = $signals;
    }

    /**
     * @return Maybe<self>
     */
    public static function open(
        Transport $transport,
        Url $server,
        Protocol $protocol,
        ElapsedPeriod $timeout,
        Clock $clock,
        Remote $remote,
        Sockets $sockets,
    ): Maybe {
        // TODO opened sockets should automatically be wrapped
        $io = IO::of($sockets->watch(...));

        /**
         * Due to the $socket->write() psalm lose the type
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidArgument
         */
        return $remote
            ->socket(
                $transport,
                $server->authority()->withoutUserInformation(),
            )
            ->flatMap(
                static fn($socket) => $socket
                    ->write($protocol->version()->pack())
                    ->maybe(),
            )
            ->map(
                static fn($socket) => $io
                    ->sockets()
                    ->clients()
                    ->wrap($socket)
                    ->timeoutAfter($timeout)
                    ->toEncoding(Str\Encoding::ascii),
            )
            ->map(static fn($socket) => new self(
                $protocol,
                Heartbeat::start($clock, $timeout),
                $socket,
                MaxChannels::unlimited(),
                MaxFrameSize::unlimited(),
                (new FrameReader)($protocol),
                SignalListener::uninstalled(),
            ))
            ->flatMap(new Start($server->authority()))
            ->flatMap(new Handshake($server->authority()))
            ->flatMap(new OpenVHost($server->path()));
    }

    /**
     * @param callable(Protocol, MaxFrameSize): Sequence<Frame> $frames
     *
     * @return Either<Failure, SideEffect>
     */
    public function respondTo(Method $method, callable $frames): Either
    {
        return $this
            ->wait($method)
            ->flatMap(
                fn() => $this->send($frames),
            );
    }

    /**
     * @param callable(Protocol, MaxFrameSize): Sequence<Frame> $frames
     *
     * @return Either<Failure, Frame>
     */
    public function request(callable $frames, Method $method, Method ...$methods): Either
    {
        return $this
            ->send($frames)
            ->flatMap(fn() => $this->wait($method, ...$methods))
            ->map(static fn($received) => $received->frame());
    }

    /**
     * @param callable(Protocol, MaxFrameSize): Sequence<Frame> $frames
     *
     * @return Either<Failure, SideEffect>
     */
    public function tell(callable $frames): Either
    {
        return $this->send($frames);
    }

    /**
     * @return Either<Failure, ReceivedFrame>
     */
    public function wait(Method ...$names): Either
    {
        return $this
            ->socket
            ->heartbeatWith(
                fn() => $this
                    ->heartbeat
                    ->frames()
                    ->map(static fn($frame) => $frame->pack()),
            )
            ->frames($this->frame)
            ->one()
            ->map(fn($frame) => ReceivedFrame::of(
                $this->asActive(),
                $frame,
            ))
            ->either()
            ->leftMap(static fn() => Failure::toReadFrame())
            ->flatMap(static fn($received) => match ($received->frame()->type()) {
                Type::heartbeat => $received->connection()->wait(...$names),
                default => $received->connection()->ensureValidFrame($received, ...$names),
            });
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        if ($this->closed()) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        return $this
            ->request(
                static fn($protocol) => $protocol->connection()->close(Close::demand()),
                Method::connectionCloseOk,
            )
            ->flatMap(fn() => $this->socket->unwrap()->close())
            ->maybe()
            ->map(static fn() => new SideEffect);
    }

    /**
     * This only modify the internal values for the connection, it doesn't
     * notify the server we applied the changes on our end. The notification is
     * done in Handshake
     *
     * @internal
     */
    public function tune(
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        ElapsedPeriod $heartbeat,
    ): self {
        $this->maxChannels = $maxChannels;
        $this->maxFrameSize = $maxFrameSize;
        $this->heartbeat->adjust($heartbeat);
        $this->socket = $this->socket->timeoutAfter($heartbeat);

        return $this;
    }

    /**
     * @internal
     */
    public function asActive(): self
    {
        $this->heartbeat->active();

        return $this;
    }

    public function listenSignals(Signals $signals, Channel $channel): self
    {
        $this->signals->install($signals, $channel);

        return $this;
    }

    /**
     * When it contains the same connection instance it means that you can still
     * use the connection, otherwise you should stop using it
     *
     * Possible failures are exceeding the max channel number, the max frame size
     * or that writting to the socket failed
     *
     * @param callable(Protocol, MaxFrameSize): Sequence<Frame> $frames
     *
     * @throws FrameChannelExceedAllowedChannelNumber
     * @throws FrameExceedAllowedSize
     *
     * @return Either<Failure, SideEffect>
     */
    private function send(callable $frames): Either
    {
        // TODO handle signals
        $data = $frames($this->protocol, $this->maxFrameSize)
            ->map(function($frame) {
                $this->maxChannels->verify($frame->channel()->toInt());

                return $frame;
            })
            ->map(static fn($frame) => $frame->pack())
            ->map(function($frame) {
                $this->maxFrameSize->verify($frame->length());

                return $frame;
            });

        return $this
            ->socket
            ->send($data)
            ->either()
            ->map(static fn() => new SideEffect)
            ->leftMap(static fn() => Failure::toSendFrame());
    }

    /**
     * @return Either<Failure, ReceivedFrame>
     */
    private function ensureValidFrame(
        ReceivedFrame $received,
        Method ...$names,
    ): Either {
        if (\count($names) === 0) {
            /** @var Either<Failure, ReceivedFrame> */
            return Either::right($received);
        }

        if ($received->frame()->type() !== Type::method) {
            // someone must have forgot a wait() call
            /** @var Either<Failure, ReceivedFrame> */
            return Either::left(Failure::unexpectedFrame());
        }

        if ($received->oneOf(...$names)) {
            /** @var Either<Failure, ReceivedFrame> */
            return Either::right($received);
        }

        if ($received->frame()->is(Method::connectionClose)) {
            /** @var Either<Failure, ReceivedFrame> */
            return $received
                ->connection()
                ->tell(static fn($protocol) => $protocol->connection()->closeOk())
                ->leftMap(static fn() => Failure::toCloseConnection())
                ->flatMap(static function() use ($received) {
                    $message = $received
                        ->frame()
                        ->values()
                        ->get(1)
                        ->keep(Instance::of(Value\ShortString::class))
                        ->map(static fn($value) => $value->original()->toString())
                        ->match(
                            static fn($message) => $message,
                            static fn() => 'Invalid message sent by server',
                        );
                    $code = $received
                        ->frame()
                        ->values()
                        ->first()
                        ->keep(Instance::of(Value\UnsignedShortInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->match(
                            static fn($code) => $code,
                            static fn() => 0,
                        );
                    $class = $received
                        ->frame()
                        ->values()
                        ->get(2)
                        ->keep(Instance::of(Value\UnsignedShortInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->filter(static fn($class) => $class !== 0);
                    $method = $received
                        ->frame()
                        ->values()
                        ->get(3)
                        ->keep(Instance::of(Value\UnsignedShortInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->filter(static fn($method) => $method !== 0);
                    $method = Maybe::all($class, $method)->map(
                        static fn(int $class, int $method) => Method::of($class, $method),
                    );

                    return Either::left(Failure::closedByServer($message, $code, $method));
                });
        }

        /** @var Either<Failure, ReceivedFrame> */
        return Either::left(Failure::unexpectedFrame());
    }

    private function closed(): bool
    {
        return $this->socket->unwrap()->closed();
    }
}
