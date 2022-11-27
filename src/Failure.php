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
    case toCloseConnection;
    case toDeleteQueue;
    case toDeleteExchange;
    case toDeclareQueue;
    case toDeclareExchange;
    case toBind;
    case toUnbind;
    case toPurge;
    case toAdjustQos;
    case toPublish;
    case toGet;
    case toConsume;
    case toAck;
    case toReject;
    case toCancel;
    case toRecover;
    case toSelect;
    case toCommit;
    case toRollback;
    case toSendFrame;
    case toReadFrame;
    case toReadMessage;
    case unexpectedFrame;
    case closedByServer;
}
