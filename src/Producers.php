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
    /** @var Map<string, Producer> */
    private Map $producers;

    /**
     * @no-named-arguments
     */
    public function __construct(Client $client, string ...$exchanges)
    {
        $this->producers = Map::of(
            ...Sequence::strings(...$exchanges)
                ->map(
                    static fn($exchange) => [$exchange, new Producer($client, $exchange)],
                )
                ->toList(),
        );
    }

    /**
     * @no-named-arguments
     */
    public static function of(Client $client, Declaration ...$exchanges): self
    {
        $exchanges = Sequence::of(...$exchanges)->map(
            static fn(Declaration $exchange): string => $exchange->name(),
        );

        return new self($client, ...$exchanges->toList());
    }

    public function get(string $exchange): Producer
    {
        return $this->producers->get($exchange)->match(
            static fn($producer) => $producer,
            static fn() => throw new \LogicException,
        );
    }

    public function contains(string $exchange): bool
    {
        return $this->producers->contains($exchange);
    }
}
