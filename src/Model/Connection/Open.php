<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\Url\PathInterface;

final class Open
{
    private $virtualHost;

    public function __construct(PathInterface $virtualHost)
    {
        $this->virtualHost = $virtualHost;
    }

    public function virtualHost(): PathInterface
    {
        return $this->virtualHost;
    }
}
