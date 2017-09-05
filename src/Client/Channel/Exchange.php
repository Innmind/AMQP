<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

use Innmind\AMQP\Model\Exchange\{
    Declaration,
    Deletion
};

interface Exchange
{
    public function declare(Declaration $command): self;
    public function delete(Deletion $command): self;
}
