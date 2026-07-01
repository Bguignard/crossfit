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
        $clientIp = trim((string) ($request->getClientIp() ?? ''));
        $visitorId = trim((string) $request->headers->get('X-MonWOD-Visitor-Id', ''));

        // Do not trust the client visitor id alone for anonymous quotas: bind quota to
        // the server-observed IP whenever Symfony can resolve one.
        $identity = $clientIp !== ''
            ? 'ip:'.strtolower($clientIp)
            : 'visitor:'.$visitorId;

        return hash_hmac('sha256', $identity, $this->appSecret);
    }
}
