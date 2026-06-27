<?php

namespace App\Services\Workout;

use App\Entity\WorkoutGeneration\WorkoutGeneration;

readonly class TeamWorkoutStructureGuidanceProvider
{
    public function buildPromptGuidance(WorkoutGeneration $workoutGeneration): string
    {
        if (!$workoutGeneration->isTeamWorkout()) {
            return "Team workout guidance: this is an individual workout. Do not use partner relay, shared reps, split-anyhow rules, synchronized work, partner holds/carries/static constraints or any other team structure.\n";
        }

        return <<<TXT
Team workout guidance: this must be explicitly written as a team workout. Use team of 2 by default. Use team of 3 only when the stimulus, format or logistics clearly justify it. Do not create team sizes above 3 for standard MonWOD generation. Choose one main team structure that fits the stimulus, workout format, time cap, movement pool and team size, then write the flow so the work-sharing rule is impossible to miss.
Audit-informed team structure weighting for this generation:
- major structure: synchronized / mixed synchro. Use synchronized blocks or a limited mixed synchro + shared-work requirement when it preserves the stimulus and realistic volume.
- regular structures: shared total reps/calories; split anyhow; alternating work / short relay.
- occasional structure: active hold/carry/static constraint while partner works, used sparingly when it makes both athletes meaningfully involved.
Alternating work / short relay includes "you go, I go", relay stations and partner alternating rounds. Prefer short relays in small sets, small calorie chunks, short distances or one compact movement at a time. Whole-round alternating is allowed only when each round is very compact.
Pick exactly one main structure, optionally with one secondary constraint. Do not merely write "team workout" without explicit sharing, switching, synchronization, relay, hold or carry rules.
Central alternating-work guardrail: do not prescribe long row/run segments, full long stations, large unbroken sets or whole long rounds if one partner would wait several minutes. If a station is long, split it into short distance/repetition/calorie switches, or choose shared reps/split-anyhow, synchronized work, active holds, carries or static constraints instead.
Guardrails: do not synchronize the entire workout if that breaks the stimulus, makes volume unrealistic or turns an engine/strength-endurance workout into an awkward bottleneck. For machines, ergs and calories, state explicitly whether athletes share one machine, alternate short calorie chunks, use separate machines, or rotate stations.

TXT;
    }

    public function buildVariantPromptGuidance(WorkoutGeneration $workoutGeneration): string
    {
        if (!$workoutGeneration->isTeamWorkout()) {
            return "Team workout concept guidance: this is an individual workout. Do not suggest partner relay, shared reps, split-anyhow rules, synchronized work, partner holds/carries/static constraints or any other team structure.\n";
        }

        return <<<TXT
Team workout concept guidance: every concept must choose one main team structure that fits the stimulus, workout format, time cap, movement pool and team size. Use team of 2 by default; use team of 3 only when justified; do not create team sizes above 3 for standard MonWOD generation. Do not write the final workout flow yet; describe the chosen team structure in the concept intent, format or summary so it can be expanded later without ambiguity.
Audit-informed team concept weighting: major structure = synchronized / mixed synchro; regular structures = shared total reps/calories, split anyhow, alternating work / short relay; occasional structure = active hold/carry/static constraint while partner works.
Alternating work / short relay covers "you go, I go", relay stations and partner alternating rounds. Keep relays short; whole-round alternating is acceptable only when each round is very compact. For long stations, describe short distance/repetition/calorie switches or choose shared reps, split-anyhow, synchro, active hold/carry/static constraint instead.

TXT;
    }
}
