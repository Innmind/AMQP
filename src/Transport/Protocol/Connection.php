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
};

interface Connection
{
    public function startOk(StartOk $command): Frame;
    public function secureOk(SecureOk $command): Frame;
    public function tuneOk(TuneOk $command): Frame;
    public function open(Open $command): Frame;
    public function close(Close $command): Frame;
    public function closeOk(): Frame;
}
