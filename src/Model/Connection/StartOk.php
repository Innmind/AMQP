<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\Url\Authority\UserInformation\{
    User,
    Password,
};

/**
 * @psalm-immutable
 */
final class StartOk
{
    private function __construct(
        private User $user,
        private Password $password,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(User $user, Password $password): self
    {
        return new self($user, $password);
    }

    #[\NoDiscard]
    public function user(): User
    {
        return $this->user;
    }

    #[\NoDiscard]
    public function password(): Password
    {
        return $this->password;
    }
}
