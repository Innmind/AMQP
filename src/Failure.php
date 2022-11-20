<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

/**
 * @psalm-immutable
 */
enum Failure
{
    case toOpenConnection;
    case toOpenChannel;
    case toCloseChannel;
    case toDeleteQueue;
    case toDeleteExchange;
    case toDeclareQueue;
    case toDeclareExchange;
    case toBind;
    case toUnbind;
    case toPurge;
    case toAdjustQos;
}
