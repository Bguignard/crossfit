<?php

namespace App\Tests;

use App\Command\PromoteAdminUserCommand;
use App\Entity\Security\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PromoteAdminUserCommandTest extends AbstractIntegrationTest
{
    public function testExistingUserCanBePromotedToAdmin(): void
    {
        $user = new User('admin@example.com');
        $user->setPassword('test-password');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $tester = new CommandTester(new PromoteAdminUserCommand($this->getEntityManager()));
        $statusCode = $tester->execute(['email' => 'ADMIN@example.com']);

        self::assertSame(Command::SUCCESS, $statusCode);

        $this->getEntityManager()->refresh($user);
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testUnknownUserCannotBePromoted(): void
    {
        $tester = new CommandTester(new PromoteAdminUserCommand($this->getEntityManager()));
        $statusCode = $tester->execute(['email' => 'missing@example.com']);

        self::assertSame(Command::FAILURE, $statusCode);
    }
}
