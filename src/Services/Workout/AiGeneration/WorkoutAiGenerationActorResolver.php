<?php

namespace App\Services\Workout\AiGeneration;

use App\Entity\Security\User;
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use Symfony\Component\HttpFoundation\Request;

readonly class WorkoutAiGenerationActorResolver
{
    public function __construct(private string $appSecret)
    {
    }

    public function resolve(Request $request, mixed $securityUser, bool $isAdmin): WorkoutAiGenerationActor
    {
        $user = $securityUser instanceof User ? $securityUser : null;
        if ($isAdmin && $user instanceof User) {
            return new WorkoutAiGenerationActor($user, WorkoutAiGenerationUsage::ACTOR_ADMIN, null);
        }
        if ($user instanceof User) {
            return new WorkoutAiGenerationActor($user, WorkoutAiGenerationUsage::ACTOR_USER, null);
        }

        return new WorkoutAiGenerationActor(null, WorkoutAiGenerationUsage::ACTOR_ANONYMOUS, $this->visitorHash($request));
    }

    private function visitorHash(Request $request): string
    {
        $visitorId = trim((string) $request->headers->get('X-MonWOD-Visitor-Id', ''));
        if ($visitorId === '') {
            $visitorId = 'ip:'.($request->getClientIp() ?? 'unknown');
        } else {
            $visitorId = 'visitor:'.$visitorId;
        }

        return hash_hmac('sha256', $visitorId, $this->appSecret);
    }
}
