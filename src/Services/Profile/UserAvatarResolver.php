<?php

namespace App\Services\Profile;

use App\Entity\Product\UserAthleteProfile;
use App\Entity\Security\User;

final class UserAvatarResolver
{
    public function avatarUrl(User $user): ?string
    {
        $profiles = array_filter(
            $user->getAthleteProfiles()->toArray(),
            static function (UserAthleteProfile $profile): bool {
                $athlete = $profile->getAthlete();

                return $athlete->getSourceName() === 'crossfit_games'
                    && $athlete->getAvatarUrl() !== null
                    && $athlete->getAvatarUrl() !== '';
            }
        );

        usort(
            $profiles,
            static fn (UserAthleteProfile $left, UserAthleteProfile $right): int => [
                $left->isPrimaryProfile() ? 0 : 1,
                $left->getCreatedAt()->format('U.u'),
            ] <=> [
                $right->isPrimaryProfile() ? 0 : 1,
                $right->getCreatedAt()->format('U.u'),
            ]
        );

        foreach ($profiles as $profile) {
            $athlete = $profile->getAthlete();
            $avatarUrl = $athlete->getAvatarUrl();
            if ($avatarUrl !== null) {
                return $avatarUrl;
            }
        }

        return null;
    }
}
