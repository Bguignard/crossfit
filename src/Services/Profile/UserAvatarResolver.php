<?php

namespace App\Services\Profile;

use App\Entity\Product\UserAthleteProfile;
use App\Entity\Security\User;

final class UserAvatarResolver
{
    public function avatarUrl(User $user): ?string
    {
        foreach ($user->getAthleteProfiles() as $profile) {
            if (!$profile instanceof UserAthleteProfile) {
                continue;
            }

            $athlete = $profile->getAthlete();
            if ($athlete->getSourceName() !== 'crossfit_games') {
                continue;
            }

            $avatarUrl = $athlete->getAvatarUrl();
            if ($avatarUrl !== null && $avatarUrl !== '') {
                return $avatarUrl;
            }
        }

        return null;
    }
}
