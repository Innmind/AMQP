<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\Immutable\Str;

final class NoFrameDetected extends RuntimeException
{
    private $content;

    public function __construct(Str $content)
    {
        parent::__construct();
        $this->content = $content;
    }

    public function content(): Str
    {
        return $this->content;
    }
}
