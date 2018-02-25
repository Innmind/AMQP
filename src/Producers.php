<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Model\Exchange\Declaration;
use Innmind\Immutable\{
    Sequence,
    Map,
};

final class Producers
{
    private $producers;

    public function __construct(Client $client, string ...$exchanges)
    {
        $this->producers = Sequence::of(...$exchanges)->reduce(
            new Map('string', Producer::class),
            static function(Map $producers, string $exchange) use ($client): Map {
                return $producers->put(
                    $exchange,
                    new Producer\Producer($client, $exchange)
                );
            }
        );
    }

    public static function fromDeclarations(Client $client, Declaration ...$exchanges): self
    {
        return new self(
            $client,
            ...Sequence::of(...$exchanges)->map(static function(Declaration $exchange): string {
                return $exchange->name();
            })
        );
    }

    public function get(string $exchange): Producer
    {
        return $this->producers->get($exchange);
    }

    public function contains(string $exchange): bool
    {
        return $this->producers->contains($exchange);
    }
}
