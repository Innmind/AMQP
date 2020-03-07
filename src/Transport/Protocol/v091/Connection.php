<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Model\Connection\StartOk,
    Model\Connection\SecureOk,
    Model\Connection\TuneOk,
    Model\Connection\Open,
    Model\Connection\Close,
    Transport\Frame,
    Transport\Protocol\Connection as ConnectionInterface,
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
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    Map,
};

final class Connection implements ConnectionInterface
{
    public function startOk(StartOk $command): Frame
    {
        /** @psalm-suppress InvalidArgument */
        $clientProperties = new Table(
            Map::of('string', Value::class)
                ('product', new LongString(Str::of('InnmindAMQP')))
                ('platform', new LongString(Str::of('PHP')))
                ('version', new LongString(Str::of('1.0')))
                ('information', new LongString(Str::of('')))
                ('copyright', new LongString(Str::of('')))
                (
                    'capabilities',
                    new Table(
                        Map::of('string', Value::class)
                            ('authentication_failure_close', new Bits(true))
                            ('publisher_confirms', new Bits(true))
                            ('consumer_cancel_notify', new Bits(true))
                            ('exchange_exchange_bindings', new Bits(true))
                            ('connection.blocked', new Bits(true))
                    )
                )
        );

        return Frame::method(
            new Channel(0),
            Methods::get('connection.start-ok'),
            $clientProperties,
            new ShortString(Str::of('AMQPLAIN')), //mechanism
            $this->response($command->user(), $command->password()),
            new ShortString(Str::of('en_US')) //locale
        );
    }

    public function secureOk(SecureOk $command): Frame
    {
        return Frame::method(
            new Channel(0),
            Methods::get('connection.secure-ok'),
            $this->response($command->user(), $command->password())
        );
    }

    public function tuneOk(TuneOk $command): Frame
    {
        return Frame::method(
            new Channel(0),
            Methods::get('connection.tune-ok'),
            UnsignedShortInteger::of(new Integer($command->maxChannels())),
            UnsignedLongInteger::of(new Integer($command->maxFrameSize())),
            UnsignedShortInteger::of(new Integer(
                (int) ($command->heartbeat()->milliseconds() / 1000)
            ))
        );
    }

    public function open(Open $command): Frame
    {
        return Frame::method(
            new Channel(0),
            Methods::get('connection.open'),
            ShortString::of(Str::of($command->virtualHost()->toString())),
            new ShortString(Str::of('')), //capabilities (reserved)
            new Bits(false) //insist (reserved)
        );
    }

    public function close(Close $command): Frame
    {
        $replyCode = 0;
        $replyText = '';
        $method = new Method(0, 0);

        if ($command->hasReply()) {
            $replyCode = $command->replyCode();
            $replyText = $command->replyText();
        }

        if ($command->causedKnown()) {
            $method = Methods::get($command->cause());
        }

        return Frame::method(
            new Channel(0),
            Methods::get('connection.close'),
            UnsignedShortInteger::of(new Integer($replyCode)),
            ShortString::of(Str::of($replyText)),
            UnsignedShortInteger::of(new Integer($method->class())),
            UnsignedShortInteger::of(new Integer($method->method()))
        );
    }

    public function closeOk(): Frame
    {
        return Frame::method(
            new Channel(0),
            Methods::get('connection.close-ok')
        );
    }

    private function response(User $user, Password $password): LongString
    {
        /** @var Map<string, Value> */
        $arguments = Map::of('string', Value::class);
        $response = new Table(
            $arguments
                ('LOGIN', LongString::of(Str::of($user->toString())))
                ('PASSWORD', LongString::of(Str::of($password->toString())))
        );
        $response = Str::of($response->pack())
            ->toEncoding('ASCII')
            ->substring(4); //skip the encoded table length integer

        return new LongString($response);
    }
}
