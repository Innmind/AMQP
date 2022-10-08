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
    Transport\Frame\Type,
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
};

final class Connection
{
    public function startOk(StartOk $command): Frame
    {
        /** @psalm-suppress InvalidArgument */
        $clientProperties = Table::of(
            Map::of(
                ['product', LongString::of(Str::of('InnmindAMQP'))],
                ['platform', LongString::of(Str::of('PHP'))],
                ['version', LongString::of(Str::of('1.0'))],
                ['information', LongString::of(Str::of(''))],
                ['copyright', LongString::of(Str::of(''))],
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

        return Frame::method(
            new Channel(0),
            Method::connectionStartOk,
            $clientProperties,
            ShortString::of(Str::of('AMQPLAIN')), // mechanism
            $this->response($command->user(), $command->password()),
            ShortString::of(Str::of('en_US')), // locale
        );
    }

    public function secureOk(SecureOk $command): Frame
    {
        return Frame::method(
            new Channel(0),
            Method::connectionSecureOk,
            $this->response($command->user(), $command->password()),
        );
    }

    public function tuneOk(TuneOk $command): Frame
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return Frame::method(
            new Channel(0),
            Method::connectionTuneOk,
            UnsignedShortInteger::of($command->maxChannels()),
            UnsignedLongInteger::of($command->maxFrameSize()),
            UnsignedShortInteger::of(
                (int) ($command->heartbeat()->milliseconds() / 1000),
            ),
        );
    }

    public function open(Open $command): Frame
    {
        return Frame::method(
            new Channel(0),
            Method::connectionOpen,
            ShortString::of(Str::of($command->virtualHost()->toString())),
            ShortString::of(Str::of('')), // capabilities (reserved)
            Bits::of(false), // insist (reserved)
        );
    }

    public function close(Close $command): Frame
    {
        [$replyCode, $replyText] = $command->response()->match(
            static fn($info) => $info,
            static fn() => [0, ''],
        );

        return Frame::method(
            new Channel(0),
            Method::connectionClose,
            UnsignedShortInteger::of($replyCode),
            ShortString::of(Str::of($replyText)),
            // we don't offer the user to specify the cause of the close because
            // it implies exposing the transport details at the model level and
            // it also depends on the state of the connection
            UnsignedShortInteger::of(0), // class
            UnsignedShortInteger::of(0), // method
        );
    }

    public function closeOk(): Frame
    {
        return Frame::method(
            new Channel(0),
            Method::connectionCloseOk,
        );
    }

    private function response(User $user, Password $password): LongString
    {
        /** @var Map<string, Value> */
        $arguments = Map::of(
            ['LOGIN', LongString::of(Str::of($user->toString()))],
            ['PASSWORD', LongString::of(Str::of($password->toString()))],
        );
        $response = Table::of($arguments);
        $response = Str::of($response->pack())
            ->toEncoding('ASCII')
            ->substring(4); // skip the encoded table length integer

        return LongString::of($response);
    }
}
