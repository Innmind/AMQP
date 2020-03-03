<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Exception\DomainException;

final class Version
{
    private int $major;
    private int $minor;
    private int $fix;

    public function __construct(int $major, int $minor, int $fix)
    {
        if (\min($major, $minor, $fix) < 0) {
            throw new DomainException;
        }

        $this->major = $major;
        $this->minor = $minor;
        $this->fix = $fix;
    }

    public function major(): int
    {
        return $this->major;
    }

    public function minor(): int
    {
        return $this->minor;
    }

    public function fix(): int
    {
        return $this->fix;
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

    public function compatibleWith(self $version): bool
    {
        if ($this->major === 0 && $version->major === 0) {
            return $this->minor === $version->minor;
        }

        if ($this->major !== $version->major) {
            return false;
        }

        return $this->minor >= $version->minor;
    }

    public function toString(): string
    {
        return \sprintf(
            "AMQP\x00%s%s%s",
            \chr($this->major),
            \chr($this->minor),
            \chr($this->fix)
        );
    }
}
