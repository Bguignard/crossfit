# API Contract

The frontend API is split into public, private, and admin surfaces.

## Public API

Public endpoints are read-only and must stay bounded.

Current public resources:

- `GET /api/workouts`
- `GET /api/workouts/{id}`
- `GET /api/movements`
- `GET /api/implements`
- `GET /api/muscles`
- `GET /api/body_parts`
- `GET /api/workout_types`
- `GET /api/workout_origins`
- `GET /api/athletes`
- `GET /api/athletes/{id}`
- `GET /api/competitions`
- `GET /api/competition_events`
- `GET /api/workout_results`

Public collections are paginated. The default page size is 25 and the maximum client-requested page size is 50.

Do not add public endpoints that dump complete datasets or trigger expensive AI/worker actions.

## Private API

Private endpoints require an authenticated user and should cover user-owned data:

- current user profile;
- linked athlete profiles;
- personal performance profiles and metrics;
- performance analysis requests;
- programming generation requests;
- future saved or favorite WODs.

This surface still needs the dedicated API authentication implementation.

## Admin API

Admin endpoints require an administrator role.

Planned admin surface:

- product/crawler metrics dashboard;
- import and worker health;
- operational counters and failures.

## CORS

The committed default CORS policy allows local development and `https://www.monwod.fr`.

Production may override `CORS_ALLOW_ORIGIN` in `.env.local`.
