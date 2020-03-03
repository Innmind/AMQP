<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\StartOk;
use Innmind\Url\Authority\UserInformation\{
    User,
    Password,
};
use PHPUnit\Framework\TestCase;

class StartOkTest extends TestCase
{
    public function testInterface()
    {
        $command = new StartOk(
            $user = User::none(),
            $password = Password::none(),
        );

        $this->assertSame($user, $command->user());
        $this->assertSame($password, $command->password());
    }
}
