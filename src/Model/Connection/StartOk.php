<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\Url\Authority\UserInformation\{
    User,
    Password,
};

final class StartOk
{
    private User $user;
    private Password $password;

    public function __construct(User $user, Password $password)
    {
        $this->user = $user;
        $this->password = $password;
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
