<?php

namespace App\Command;

use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote-admin',
    description: 'Grant ROLE_ADMIN to an existing user.',
)]
class PromoteAdminUserCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the user to promote.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = strtolower(trim((string) $input->getArgument('email')));

        if ($email === '') {
            $io->error('Email is required.');

            return Command::FAILURE;
        }

        $user = $this->findUserByEmail($email);
        if ($user === null) {
            $io->error(sprintf('User "%s" was not found.', $email));

            return Command::FAILURE;
        }

        $roles = array_values(array_diff($user->getRoles(), ['ROLE_USER']));
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $this->entityManager->flush();
        }

        $io->success(sprintf('User "%s" is now admin.', $email));

        return Command::SUCCESS;
    }

    private function findUserByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->entityManager
            ->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('LOWER(user.email) = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
    }
}
