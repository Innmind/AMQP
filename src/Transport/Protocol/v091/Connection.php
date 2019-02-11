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
    UserInterface,
    PasswordInterface,
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
        $clientProperties = new Table(
            Map::of('string', Value::class)
                ('product', new LongString(new Str('InnmindAMQP')))
                ('platform', new LongString(new Str('PHP')))
                ('version', new LongString(new Str('1.0')))
                ('information', new LongString(new Str('')))
                ('copyright', new LongString(new Str('')))
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
            new ShortString(new Str('AMQPLAIN')), //mechanism
            $this->response($command->user(), $command->password()),
            new ShortString(new Str('en_US')) //locale
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
            new UnsignedShortInteger(new Integer($command->maxChannels())),
            new UnsignedLongInteger(new Integer($command->maxFrameSize())),
            new UnsignedShortInteger(new Integer(
                (int) ($command->heartbeat()->milliseconds() / 1000)
            ))
        );
    }

    public function open(Open $command): Frame
    {
        return Frame::method(
            new Channel(0),
            Methods::get('connection.open'),
            new ShortString(new Str((string) $command->virtualHost())),
            new ShortString(new Str('')), //capabilities (reserved)
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
            new UnsignedShortInteger(new Integer($replyCode)),
            new ShortString(new Str($replyText)),
            new UnsignedShortInteger(new Integer($method->class())),
            new UnsignedShortInteger(new Integer($method->method()))
        );
    }

    public function closeOk(): Frame
    {
        return Frame::method(
            new Channel(0),
            Methods::get('connection.close-ok')
        );
    }

    private function response(UserInterface $user, PasswordInterface $password): LongString
    {
        $response = new Table(
            Map::of('string', Value::class)
                ('LOGIN', new LongString(new Str((string) $user)))
                ('PASSWORD', new LongString(new Str((string) $password)))
        );
        $response = Str::of((string) $response)
            ->toEncoding('ASCII')
            ->substring(4); //skip the encoded table length integer

        return new LongString($response);
    }
}
