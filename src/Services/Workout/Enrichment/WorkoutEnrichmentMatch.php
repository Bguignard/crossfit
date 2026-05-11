<?php

namespace App\Services\Workout\Enrichment;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;

final class WorkoutEnrichmentMatch
{
    /**
     * @param list<Movement>  $movements
     * @param list<Implement> $implements
     * @param list<string>    $ambiguousTerms
     */
    public function __construct(
        public readonly array $movements,
        public readonly array $implements,
        public readonly array $ambiguousTerms,
    ) {
    }

    public function hasMatches(): bool
    {
        return $this->movements !== [] || $this->implements !== [];
    }
}
