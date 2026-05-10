<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Security\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class ProductIdentityModelTest extends AbstractIntegrationTest
{
    public function testUserCanLinkSeveralExternalAthleteProfiles(): void
    {
        $user = (new User('bruno@example.com'))
            ->setPassword('hashed-password')
            ->setDisplayName('Bruno');
        $scoringFitAthlete = new Athlete('Bruno Guignard', 'scoring_fit', 'sf-123');
        $competitionCornerAthlete = new Athlete('Bruno G.', 'competition_corner', 'cc-456');
        $primaryProfile = (new UserAthleteProfile($user, $scoringFitAthlete))
            ->setPrimaryProfile(true)
            ->markVerified(new \DateTimeImmutable('2026-05-10 12:00:00'));
        $secondaryProfile = new UserAthleteProfile($user, $competitionCornerAthlete, UserAthleteProfile::LINK_FOLLOWED);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->persist($scoringFitAthlete);
        $em->persist($competitionCornerAthlete);
        $em->persist($primaryProfile);
        $em->persist($secondaryProfile);
        $em->flush();
        $em->clear();

        /** @var User|null $storedUser */
        $storedUser = $this->getRepository(User::class)->findOneBy(['email' => 'bruno@example.com']);

        self::assertNotNull($storedUser);
        self::assertCount(2, $storedUser->getAthleteProfiles());
        self::assertTrue(
            $storedUser->getAthleteProfiles()->exists(
                static fn (int $index, UserAthleteProfile $profile): bool => $profile->isPrimaryProfile()
                    && $profile->getAthlete()->getSourceName() === 'scoring_fit'
                    && $profile->getVerifiedAt() !== null
            )
        );
        self::assertTrue(
            $storedUser->getAthleteProfiles()->exists(
                static fn (int $index, UserAthleteProfile $profile): bool => $profile->getLinkType() === UserAthleteProfile::LINK_FOLLOWED
                    && $profile->getAthlete()->getSourceName() === 'competition_corner'
            )
        );
    }

    public function testUserCannotLinkSameAthleteTwice(): void
    {
        $user = (new User('tia@example.com'))->setPassword('hashed-password');
        $athlete = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'athlete-789');

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->persist($athlete);
        $em->persist(new UserAthleteProfile($user, $athlete));
        $em->flush();

        $this->expectException(UniqueConstraintViolationException::class);

        $em->persist(new UserAthleteProfile($user, $athlete, UserAthleteProfile::LINK_FOLLOWED));
        $em->flush();
    }

    public function testSeveralUsersCanLinkTheSameExternalAthleteProfile(): void
    {
        $firstUser = (new User('first@example.com'))->setPassword('hashed-password');
        $secondUser = (new User('second@example.com'))->setPassword('hashed-password');
        $athlete = new Athlete('Mat Fraser', 'crossfit_games', 'athlete-456');

        $em = $this->getEntityManager();
        $em->persist($firstUser);
        $em->persist($secondUser);
        $em->persist($athlete);
        $em->persist(new UserAthleteProfile($firstUser, $athlete));
        $em->persist(new UserAthleteProfile($secondUser, $athlete));
        $em->flush();
        $em->clear();

        /** @var Athlete|null $storedAthlete */
        $storedAthlete = $this->getRepository(Athlete::class)->findOneBy([
            'sourceName' => 'crossfit_games',
            'externalId' => 'athlete-456',
        ]);

        self::assertNotNull($storedAthlete);
        self::assertCount(2, $storedAthlete->getLinkedUserProfiles());
    }
}
