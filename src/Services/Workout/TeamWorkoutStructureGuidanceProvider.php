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
Team workout guidance: this must be explicitly written as a team workout. Use team-of-2 unless another team size is clearly better for the stimulus. Choose one main team structure that fits the stimulus, workout format, time cap, movement pool and team size, then write the flow so the work-sharing rule is impossible to miss.
Team structure taxonomy available for this generation:
- synchronized block: athletes work at the same time on clearly marked synchronized reps only where it preserves the stimulus and realistic volume.
- shared total reps/calories, split anyhow: the team completes one total amount and may divide work freely, with clear switching rules when needed.
- short "you go, I go" switches: relay only in small sets, small calorie chunks, short distances or one compact movement at a time.
- partner alternating rounds: one athlete completes a short round while the other rests or holds, only when each round is compact enough to avoid long idle windows.
- relay stations: athletes rotate through stations with explicit handoffs and no vague waiting.
- mixed synchro + shared work: combine a limited synchronized requirement with shared reps when that better matches the intended stimulus.
- active hold/carry/static constraint while partner works: one athlete holds, carries or maintains a position while the other completes short work chunks.
Pick exactly one main structure, optionally with one secondary constraint. Do not merely write "team workout" without explicit sharing, switching, synchronization, relay, hold or carry rules.
Central "you go, I go" constraint: use short relays only. Do not prescribe long row/run segments, full long stations, large unbroken sets or whole long rounds as "you go, I go" if one partner would wait several minutes. If a station is long, split it into short distance/repetition/calorie switches, or choose shared reps/split-anyhow, synchronized work, active holds, carries or static constraints instead.
Guardrails: do not synchronize the entire workout if that breaks the stimulus, makes volume unrealistic or turns an engine/strength-endurance workout into an awkward bottleneck. For machines, ergs and calories, state explicitly whether athletes share one machine, alternate short calorie chunks, use separate machines, or rotate stations.

TXT;
    }

    public function buildVariantPromptGuidance(WorkoutGeneration $workoutGeneration): string
    {
        if (!$workoutGeneration->isTeamWorkout()) {
            return "Team workout concept guidance: this is an individual workout. Do not suggest partner relay, shared reps, split-anyhow rules, synchronized work, partner holds/carries/static constraints or any other team structure.\n";
        }

        return <<<TXT
Team workout concept guidance: every concept must choose one main team structure that fits the stimulus, workout format, time cap, movement pool and team size. Do not write the final workout flow yet; describe the chosen team structure in the concept intent, format or summary so it can be expanded later without ambiguity.
Available team concept structures: synchronized block; shared total reps/calories split anyhow; short "you go, I go" switches; partner alternating rounds; relay stations; mixed synchro + shared work; active hold/carry/static constraint while partner works.
Central "you go, I go" constraint for concepts: short relays only. Do not propose long row/run segments, full long stations, large unbroken sets or whole long rounds as "you go, I go" if one partner would wait several minutes. For long stations, describe short distance/repetition/calorie switches or choose shared reps, split-anyhow, synchro, active hold/carry/static constraint instead.

TXT;
    }
}
