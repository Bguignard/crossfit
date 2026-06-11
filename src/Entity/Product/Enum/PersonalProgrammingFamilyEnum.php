<?php

namespace App\Entity\Product\Enum;

enum PersonalProgrammingFamilyEnum: string
{
    case CROSSFIT_GENERAL = 'crossfit_general';
    case PRIORITY_WEAKNESSES = 'priority_weaknesses';
    case STRENGTH = 'strength';
    case GYMNASTICS = 'gymnastics';
    case WEIGHTLIFTING = 'weightlifting';
    case ENGINE_CARDIO = 'engine_cardio';
    case HYROX = 'hyrox';

    public static function fromLegacyPurpose(?string $purpose): self
    {
        $purpose = strtolower(trim((string) $purpose));

        if ($purpose === '') {
            return self::CROSSFIT_GENERAL;
        }

        if (str_contains($purpose, 'gym')) {
            return self::GYMNASTICS;
        }

        if (str_contains($purpose, 'halter') || str_contains($purpose, 'weightlift') || str_contains($purpose, 'snatch') || str_contains($purpose, 'clean')) {
            return self::WEIGHTLIFTING;
        }

        if (str_contains($purpose, 'engine') || str_contains($purpose, 'cardio') || str_contains($purpose, 'mono')) {
            return self::ENGINE_CARDIO;
        }

        if (str_contains($purpose, 'hyrox')) {
            return self::HYROX;
        }

        if (str_contains($purpose, 'strength') || str_contains($purpose, 'force') || str_contains($purpose, 'renfo')) {
            return self::STRENGTH;
        }

        if (str_contains($purpose, 'weakness') || str_contains($purpose, 'faiblesse') || str_contains($purpose, 'accessory') || str_contains($purpose, 'cible')) {
            return self::PRIORITY_WEAKNESSES;
        }

        return self::CROSSFIT_GENERAL;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $family): string => $family->value,
            self::cases()
        );
    }
}
