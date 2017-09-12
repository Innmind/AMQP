<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic\Get;

use Innmind\AMQP\Client\Channel\Basic\Get;

final class GetEmpty implements Get
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $consume): void
    {
        //no message received
    }
}
