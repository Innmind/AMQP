<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

enum State
{
    case opening;
    case opened;
    case closed;
}
