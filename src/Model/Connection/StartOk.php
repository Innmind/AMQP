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
    private User $user;
    private Password $password;

    private function __construct(User $user, Password $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @psalm-pure
     */
    public static function of(User $user, Password $password): self
    {
        return new self($user, $password);
    }

    public function user(): User
    {
        return $this->user;
    }

    public function password(): Password
    {
        return $this->password;
    }
}
