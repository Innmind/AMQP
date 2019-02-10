<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\Stream\Readable;

final class NoFrameDetected extends RuntimeException
{
    private $content;

    public function __construct(Readable $content)
    {
        parent::__construct();
        $this->content = $content;
    }

    public function content(): Readable
    {
        return $this->content;
    }
}
