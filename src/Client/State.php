<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

/**
 * @internal
 */
final class State
{
    private mixed $userState;

    private function __construct(mixed $userState)
    {
        $this->userState = $userState;
    }

    public static function of(mixed $userState): self
    {
        return new self($userState);
    }

    public function userState(): mixed
    {
        return $this->userState;
    }
}
