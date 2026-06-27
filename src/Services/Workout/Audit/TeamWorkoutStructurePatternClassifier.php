<?php

namespace App\Services\Workout\Audit;

final readonly class TeamWorkoutStructurePatternClassifier
{
    public const SYNCHRONIZED = 'synchronized';
    public const SPLIT_ANYHOW = 'split_anyhow';
    public const YOU_GO_I_GO = 'you_go_i_go';
    public const RELAY = 'relay';
    public const SHARED_TOTAL = 'shared_total';
    public const ACTIVE_HOLD_CONSTRAINT = 'active_hold_constraint';
    public const PARTNER_ALTERNATING_ROUNDS = 'partner_alternating_rounds';

    /**
     * @return array<string, string>
     */
    public function patternLabels(): array
    {
        return [
            self::SYNCHRONIZED => 'Synchronized / synchro / sync',
            self::SPLIT_ANYHOW => 'Split reps / split anyhow / partition anyhow',
            self::YOU_GO_I_GO => 'You go I go / alternating short switches',
            self::RELAY => 'Relay / relais / handoff',
            self::SHARED_TOTAL => 'Shared total reps/calories as a team',
            self::ACTIVE_HOLD_CONSTRAINT => 'Active hold/carry/static constraint while partner works',
            self::PARTNER_ALTERNATING_ROUNDS => 'Partner alternating rounds',
        ];
    }

    /**
     * @return array{patterns: list<string>, teamSizes: list<string>}
     */
    public function classify(string $flow): array
    {
        $text = $this->normalize($flow);
        $patterns = [];

        if ($this->matches($text, '/\b(sync|synchro|synchroni[sz]ed|synchroni[sz]ation)\b/')) {
            $patterns[] = self::SYNCHRONIZED;
        }

        if ($this->matches($text, '/\b(split|partition|divide)\b.{0,45}\b(anyhow|any way|as desired|as needed|freely|reps?|work)\b|\bshar\w*\b.{0,35}\b(reps?|calories?|cals?)\b/')) {
            $patterns[] = self::SPLIT_ANYHOW;
        }

        if ($this->matches($text, '/\byou\s*[- ]?\s*go\s*(?:[,\/&+ ]|then)+\s*i\s*[- ]?\s*go\b|\bi\s*[- ]?\s*go\s*(?:[,\/&+ ]|then)+\s*you\s*[- ]?\s*go\b|\bygigo\b|\balternat\w*\s+every\b|\b(?:switch|rotate)\s+every\b|\bthen\s+switch\b|\b(partners?|teammates?|athletes?|pairs?)\b.{0,45}\b(alternat\w*|switch\w*|rotat\w*)\b|\b(alternat\w*|switch\w*|rotat\w*)\b.{0,45}\b(partners?|teammates?|athletes?|pairs?)\b/')) {
            $patterns[] = self::YOU_GO_I_GO;
        }

        if ($this->matches($text, '/\b(relay|relais|handoffs?|hand offs?|tag in|tag out|tag-in|tag-out)\b/')) {
            $patterns[] = self::RELAY;
        }

        if ($this->matches($text, '/\b(?:total\b(?!\s+(?:rounds?|attempts?))|accumulate|complete as a team|team total)\b.{0,65}\b(reps?|calories?|cals?|meters?|metres?|work)\b|\b\d+\s+total\s+(reps?|calories?|cals?|meters?|metres?)\b|\bcomplete\b.{0,20}\b\d+\s+(reps?|calories?|cals?|meters?|metres?)\s+total\b|\bcomplete\b.{0,20}\b\d+\s+(reps?|calories?|cals?|meters?|metres?)\b.{0,20}\bas a team\b/')) {
            $patterns[] = self::SHARED_TOTAL;
        }

        if ($this->matches($text, '/\b(while|whilst|pendant que)\b.{0,90}\b(partner|teammate|athlete|pair)\b.{0,90}\b(hold|holds|holding|carry|carries|carrying|static|plank|hang)\b|\b(partner|teammate|athlete|pair)\b.{0,70}\b(hold|holds|holding|carry|carries|carrying|static|plank|hang)\b.{0,70}\b(while|whilst)\b/')) {
            $patterns[] = self::ACTIVE_HOLD_CONSTRAINT;
        }

        if ($this->matches($text, '/\b(partners?|teammates?|athletes?|pairs?)\b.{0,45}\balternat\w*\b.{0,45}\brounds?\b/')) {
            $patterns[] = self::PARTNER_ALTERNATING_ROUNDS;
        }

        return [
            'patterns' => array_values(array_unique($patterns)),
            'teamSizes' => $this->detectTeamSizes($text),
        ];
    }

    /**
     * @return list<string>
     */
    private function detectTeamSizes(string $text): array
    {
        $teamSizes = [];

        if (preg_match_all('/\bteams?\s*(?:of|de|-)?\s*(\d{1,2}|two|three|four|five|six|seven|eight|nine|ten)\b|\bteam-of-(\d{1,2}|two|three|four|five|six|seven|eight|nine|ten)\b/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $size = $match[1] !== '' ? $match[1] : $match[2];
                $teamSizes[] = 'team_of_'.$this->normalizeTeamSize($size);
            }
        }

        if ($this->matches($text, '/\b(pairs?|duos?)\b/')) {
            $teamSizes[] = 'team_of_2';
        }

        if ($this->matches($text, '/\btrios?\b/')) {
            $teamSizes[] = 'team_of_3';
        }

        return array_values(array_unique($teamSizes));
    }

    private function normalizeTeamSize(string $size): string
    {
        return match ($size) {
            'two' => '2',
            'three' => '3',
            'four' => '4',
            'five' => '5',
            'six' => '6',
            'seven' => '7',
            'eight' => '8',
            'nine' => '9',
            'ten' => '10',
            default => $size,
        };
    }

    private function matches(string $text, string $pattern): bool
    {
        return preg_match($pattern.'u', $text) === 1;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = strtr($text, [
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'ç' => 'c',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
        ]);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
