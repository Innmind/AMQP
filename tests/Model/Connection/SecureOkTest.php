<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\SecureOk;
use Innmind\Url\Authority\UserInformation\{
    UserInterface,
    PasswordInterface
};
use PHPUnit\Framework\TestCase;

class SecureOkTest extends TestCase
{
    public function testInterface()
    {
        $command = new SecureOk(
            $user = $this->createMock(UserInterface::class),
            $password = $this->createMock(PasswordInterface::class)
        );

        $this->assertSame($user, $command->user());
        $this->assertSame($password, $command->password());
    }
}
