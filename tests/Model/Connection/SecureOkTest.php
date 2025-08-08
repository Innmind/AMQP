<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\SecureOk;
use Innmind\Url\Authority\UserInformation\{
    User,
    Password,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class SecureOkTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = SecureOk::of(
            $user = User::none(),
            $password = Password::none(),
        );

        $this->assertSame($user, $command->user());
        $this->assertSame($password, $command->password());
    }
}
