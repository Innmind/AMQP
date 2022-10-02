<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\Url\Path;

/**
 * @psalm-immutable
 */
final class Open
{
    private Path $virtualHost;

    public function __construct(Path $virtualHost)
    {
        $this->virtualHost = $virtualHost;
    }

    public function virtualHost(): Path
    {
        return $this->virtualHost;
    }
}
