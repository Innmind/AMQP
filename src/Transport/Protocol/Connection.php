<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Connection\StartOk,
    Model\Connection\SecureOk,
    Model\Connection\TuneOk,
    Model\Connection\Open,
    Model\Connection\Close,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\UnsignedLongInteger,
};
use Innmind\Url\Authority\UserInformation\{
    User,
    Password,
};
use Innmind\Immutable\{
    Str,
    Map,
    Sequence,
};

/**
 * @internal
 */
final class Connection
{
    /**
     * @return Sequence<Frame>
     */
    public function startOk(StartOk $command): Sequence
    {
        /** @psalm-suppress InvalidArgument */
        $clientProperties = Table::of(
            Map::of(
                ['product', LongString::literal('InnmindAMQP')],
                ['platform', LongString::literal('PHP')],
                ['version', LongString::literal('1.0')],
                ['information', LongString::literal('')],
                ['copyright', LongString::literal('')],
                [
                    'capabilities',
                    Table::of(
                        Map::of(
                            ['authentication_failure_close', Bits::of(true)],
                            ['publisher_confirms', Bits::of(true)],
                            ['consumer_cancel_notify', Bits::of(true)],
                            ['exchange_exchange_bindings', Bits::of(true)],
                            ['connection.blocked', Bits::of(true)],
                        ),
                    ),
                ],
            ),
        );

        return Sequence::of(Frame::method(
            new Channel(0),
            Method::connectionStartOk,
            $clientProperties,
            ShortString::literal('AMQPLAIN'), // mechanism
            $this->response($command->user(), $command->password()),
            ShortString::literal('en_US'), // locale
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function secureOk(SecureOk $command): Sequence
    {
        return Sequence::of(Frame::method(
            new Channel(0),
            Method::connectionSecureOk,
            $this->response($command->user(), $command->password()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function tuneOk(TuneOk $command): Sequence
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return Sequence::of(Frame::method(
            new Channel(0),
            Method::connectionTuneOk,
            UnsignedShortInteger::internal($command->maxChannels()),
            UnsignedLongInteger::internal($command->maxFrameSize()),
            UnsignedShortInteger::of(
                $command
                    ->heartbeat()
                    ->asPeriod()
                    ->seconds(),
            ),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function open(Open $command): Sequence
    {
        return Sequence::of(Frame::method(
            new Channel(0),
            Method::connectionOpen,
            ShortString::of(Str::of($command->virtualHost()->toString())),
            ShortString::literal(''), // capabilities (reserved)
            Bits::of(false), // insist (reserved)
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function close(Close $command): Sequence
    {
        [$replyCode, $replyText] = $command->response()->match(
            static fn($info) => $info,
            static fn() => [0, ''],
        );

        return Sequence::of(Frame::method(
            new Channel(0),
            Method::connectionClose,
            UnsignedShortInteger::internal($replyCode),
            ShortString::of(Str::of($replyText)),
            // we don't offer the user to specify the cause of the close because
            // it implies exposing the transport details at the model level and
            // it also depends on the state of the connection
            UnsignedShortInteger::internal(0), // class
            UnsignedShortInteger::internal(0), // method
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function closeOk(): Sequence
    {
        return Sequence::of(Frame::method(
            new Channel(0),
            Method::connectionCloseOk,
        ));
    }

    private function response(User $user, Password $password): LongString
    {
        /** @var Map<string, Value> */
        $arguments = Map::of(
            ['LOGIN', LongString::of(Str::of($user->toString()))],
            ['PASSWORD', LongString::of(Str::of($password->toString()))],
        );
        $response = Table::of($arguments);
        $response = $response
            ->pack()
            ->toEncoding(Str\Encoding::ascii)
            ->substring(4); // skip the encoded table length integer

        return LongString::of($response);
    }
}
