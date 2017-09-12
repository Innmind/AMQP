<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Transport\Protocol\Version;

final class VersionNotUsable extends RuntimeException
{
    public function __construct(Version $version)
    {
        parent::__construct(sprintf(
            '%s.%s.%s',
            $version->major(),
            $version->minor(),
            $version->fix()
        ));
    }
}
