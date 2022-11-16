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

    private function __construct(Path $virtualHost)
    {
        $this->virtualHost = $virtualHost;
    }

    /**
     * @psalm-pure
     */
    public static function of(Path $virtualHost): self
    {
        return new self($virtualHost);
    }

    public function virtualHost(): Path
    {
        return $this->virtualHost;
    }
}
