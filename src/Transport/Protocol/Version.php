<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Exception\DomainException;

final class Version
{
    private $major;
    private $minor;
    private $fix;

    public function __construct(int $major, int $minor, int $fix)
    {
        if (min($major, $minor, $fix) < 0) {
            throw new DomainException;
        }

        $this->major = $major;
        $this->minor = $minor;
        $this->fix = $fix;
    }

    public function higherThan(self $version): bool
    {
        if ($this->major !== $version->major) {
            return $this->major > $version->major;
        }

        if ($this->minor !== $version->minor) {
            return $this->minor > $version->minor;
        }

        return $this->fix > $version->fix;
    }

    public function __toString(): string
    {
        return sprintf(
            "AMQP\x00%s%s%s",
            chr($this->major),
            chr($this->minor),
            chr($this->fix)
        );
    }
}
