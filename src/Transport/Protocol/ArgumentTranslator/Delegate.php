<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\ArgumentTranslator;

use Innmind\AMQP\{
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame\Value,
    Exception\ValueNotTranslatable,
};

final class Delegate implements ArgumentTranslator
{
    /** @var list<ArgumentTranslator> */
    private array $translators;

    public function __construct(ArgumentTranslator ...$translators)
    {
        $this->translators = $translators;
    }

    public function __invoke($value): Value
    {
        foreach ($this->translators as $translate) {
            try {
                return $translate($value);
            } catch (ValueNotTranslatable $e) {
                // pass
            }
        }

        throw new ValueNotTranslatable($value);
    }
}
