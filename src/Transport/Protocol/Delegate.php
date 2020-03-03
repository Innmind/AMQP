<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Protocol,
    Transport\Frame\Method,
    Exception\VersionNotUsable,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class Delegate implements Protocol
{
    private Sequence $protocols;
    private Protocol $inUse;

    public function __construct(Protocol $first, Protocol ...$protocols)
    {
        $protocols = Sequence::of(Protocol::class, $first, ...$protocols)->sort(static function(Protocol $a, Protocol $b): bool {
            return $b->version()->higherThan($a->version());
        });
        $this->inUse = $protocols->first();
        $this->protocols = $protocols;
    }

    public function version(): Version
    {
        return $this->inUse->version();
    }

    public function use(Version $version): Protocol
    {
        $protocols = $this
            ->protocols
            ->filter(static function(Protocol $protocol) use ($version): bool {
                try {
                    $protocol->use($version);

                    return true;
                } catch (VersionNotUsable $e) {
                    return false;
                }
            });

        if ($protocols->size() === 0) {
            throw new VersionNotUsable($version);
        }

        $this->inUse = $protocols->first();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function read(Method $method, Readable $arguments): Sequence
    {
        return $this->inUse->read($method, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function readHeader(Readable $arguments): Sequence
    {
        return $this->inUse->readHeader($arguments);
    }

    public function method(string $name): Method
    {
        return $this->inUse->method($name);
    }

    public function connection(): Connection
    {
        return $this->inUse->connection();
    }

    public function channel(): Channel
    {
        return $this->inUse->channel();
    }

    public function exchange(): Exchange
    {
        return $this->inUse->exchange();
    }

    public function queue(): Queue
    {
        return $this->inUse->queue();
    }

    public function basic(): Basic
    {
        return $this->inUse->basic();
    }

    public function transaction(): Transaction
    {
        return $this->inUse->transaction();
    }
}
