<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Model\Exchange\Declaration;
use Innmind\Immutable\{
    Sequence,
    Map,
};
use function Innmind\Immutable\unwrap;

final class Producers
{
    /** @var Map<string, Producer> */
    private Map $producers;

    public function __construct(Client $client, string ...$exchanges)
    {
        /** @var Map<string, Producer> */
        $this->producers = Sequence::strings(...$exchanges)->toMapOf(
            'string',
            Producer::class,
            static function(string $exchange) use ($client): \Generator {
                yield $exchange => new Producer\Producer($client, $exchange);
            },
        );
    }

    public static function fromDeclarations(Client $client, Declaration ...$exchanges): self
    {
        $exchanges = Sequence::of(Declaration::class, ...$exchanges)->mapTo(
            'string',
            static fn(Declaration $exchange): string => $exchange->name(),
        );

        return new self(
            $client,
            ...unwrap($exchanges),
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
