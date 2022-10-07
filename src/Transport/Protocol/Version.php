<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Exception\DomainException;
use Innmind\Immutable\Str;

final class Version
{
    private int $major;
    private int $minor;
    private int $fix;

    public function __construct(int $major, int $minor, int $fix)
    {
        if (\min($major, $minor, $fix) < 0) {
            throw new DomainException("$major.$minor.$fix");
        }

        $this->major = $major;
        $this->minor = $minor;
        $this->fix = $fix;
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
            '%s.%s.%s',
            $this->major,
            $this->minor,
            $this->fix,
        );
    }

    public function pack(): Str
    {
        return Str::of(\sprintf(
            "AMQP\x00%s%s%s",
            \chr($this->major),
            \chr($this->minor),
            \chr($this->fix),
        ));
    }
}
