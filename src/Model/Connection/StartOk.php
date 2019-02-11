<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\Url\Authority\UserInformation\{
    UserInterface,
    PasswordInterface,
};

final class StartOk
{
    private $user;
    private $password;

    public function __construct(UserInterface $user, PasswordInterface $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function user(): UserInterface
    {
        return $this->user;
    }

    public function password(): PasswordInterface
    {
        return $this->password;
    }
}
