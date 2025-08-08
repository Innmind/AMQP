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
    Model\Connection\TuneOk,
    Failure,
};
use Innmind\OperatingSystem\CurrentProcess\Signals;
use Innmind\IO\{
    Sockets\Clients\Client,
    Sockets\Internet\Transport,
    Frame as IOFrame,
};
use Innmind\Url\Url;
use Innmind\TimeContinuum\{
    Period,
    Clock,
};
use Innmind\OperatingSystem\Remote;
use Innmind\Immutable\{
    Str,
    Attempt,
    Maybe,
    Sequence,
    SideEffect,
    Predicate\Instance,
};

/**
 * @internal You should use the Client instead
 */
final class Connection
{
    /**
     * @param IOFrame<Frame> $frame
     */
    private function __construct(
        private Protocol $protocol,
        private Heartbeat $heartbeat,
        private Client $socket,
        private MaxChannels $maxChannels,
        private MaxFrameSize $maxFrameSize,
        private IOFrame $frame,
        private SignalListener $signals,
        private bool $closed = false,
    ) {
    }

    /**
     * @return Attempt<self>
     */
    public static function open(
        Transport $transport,
        Url $server,
        Protocol $protocol,
        Period $timeout,
        Clock $clock,
        Remote $remote,
    ): Attempt {
        return $remote
            ->socket(
                $transport,
                $server->authority()->withoutUserInformation(),
            )
            ->map(
                static fn($socket) => $socket
                    ->timeoutAfter($timeout)
                    ->toEncoding(Str\Encoding::ascii),
            )
            ->flatMap(
                static fn($socket) => $socket
                    ->sink(Sequence::of($protocol->version()->pack()))
                    ->map(static fn() => $socket),
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
     * @return Attempt<SideEffect>
     */
    public function respondTo(Method $method, callable $frames): Attempt
    {
        return $this
            ->wait($method)
            ->flatMap(
                fn() => $this->sendFrames($frames),
            );
    }

    /**
     * @param callable(Protocol, MaxFrameSize): Sequence<Frame> $frames
     *
     * @return Attempt<Frame>
     */
    public function request(callable $frames, Method $method, Method ...$methods): Attempt
    {
        return $this
            ->sendFrames($frames)
            ->flatMap(fn() => $this->wait($method, ...$methods))
            ->map(static fn($received) => $received->frame());
    }

    /**
     * @param callable(Protocol, MaxFrameSize): Sequence<Frame> $frames
     *
     * @return Attempt<SideEffect>
     */
    public function send(callable $frames): Attempt
    {
        return $this->sendFrames($frames);
    }

    /**
     * @return Attempt<ReceivedFrame>
     */
    public function wait(Method ...$names): Attempt
    {
        return $this
            ->socket
            ->heartbeatWith(
                fn() => $this
                    ->heartbeat
                    ->frames()
                    ->map(static fn($frame) => $frame->pack()),
            )
            ->abortWhen($this->signals->notified(...))
            ->frames($this->frame)
            ->one()
            ->map(ReceivedFrame::of(...))
            ->map($this->flagActive(...))
            ->eitherWay(
                fn($received) => match ($received->frame()->type()) {
                    Type::heartbeat => $this->wait(...$names),
                    default => $this->ensureValidFrame($received, ...$names),
                },
                fn() => $this->signals->close(
                    $this,
                    static fn() => Attempt::error(Failure::toReadFrame()),
                ),
            );
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function close(): Attempt
    {
        $this->signals->uninstall();

        return $this
            ->request(
                static fn($protocol) => $protocol->connection()->close(Close::demand()),
                Method::connectionCloseOk,
            )
            ->flatMap(fn() => $this->socket->close())
            ->map(SideEffect::identity(...));
    }

    /**
     * @return Attempt<self>
     */
    public function tune(
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        Period $heartbeat,
    ): Attempt {
        return $this
            ->send(static fn($protocol) => $protocol->connection()->tuneOk(
                TuneOk::of(
                    $maxChannels,
                    $maxFrameSize,
                    $heartbeat,
                ),
            ))
            ->map(fn() => new self(
                $this->protocol,
                $this->heartbeat->adjust($heartbeat),
                $this->socket->timeoutAfter($heartbeat),
                $maxChannels,
                $maxFrameSize,
                $this->frame,
                $this->signals,
            ));
    }

    public function listenSignals(Signals $signals, Channel $channel): void
    {
        $this->signals->install($signals, $channel);
    }

    private function flagActive(ReceivedFrame $received): ReceivedFrame
    {
        $this->heartbeat->active();

        return $received;
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
     * @return Attempt<SideEffect>
     */
    private function sendFrames(callable $frames): Attempt
    {
        $data = $frames($this->protocol, $this->maxFrameSize)->map(
            fn($frame) => $this
                ->maxChannels
                ->verify($frame->channel()->toInt())
                ->map(static fn() => $frame->pack())
                ->flatMap(
                    fn($frame) => $this
                        ->maxFrameSize
                        ->verify($frame->length())
                        ->map(static fn() => $frame),
                ),
        );

        return $this
            ->socket
            ->abortWhen($this->signals->notified(...))
            ->sinkAttempts($data)
            ->eitherWay(
                static fn() => Attempt::result(SideEffect::identity()),
                fn() => $this->signals->close(
                    $this,
                    static fn() => Attempt::error(Failure::toSendFrame()),
                ),
            );
    }

    /**
     * @return Attempt<ReceivedFrame>
     */
    private function ensureValidFrame(
        ReceivedFrame $received,
        Method ...$names,
    ): Attempt {
        if (\count($names) === 0) {
            return Attempt::result($received);
        }

        if ($received->frame()->type() !== Type::method) {
            // someone must have forgot a wait() call
            /** @var Attempt<ReceivedFrame> */
            return Attempt::error(Failure::unexpectedFrame());
        }

        if ($received->oneOf(...$names)) {
            return Attempt::result($received);
        }

        if ($received->is(Method::connectionClose)) {
            /** @var Attempt<ReceivedFrame> */
            return $this
                ->send(static fn($protocol) => $protocol->connection()->closeOk())
                ->mapError(Failure::as(Failure::toCloseConnection()))
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

                    return Attempt::error(Failure::closedByServer($message, $code, $method));
                });
        }

        /** @var Attempt<ReceivedFrame> */
        return Attempt::error(Failure::unexpectedFrame());
    }
}
