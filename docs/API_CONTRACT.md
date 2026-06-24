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

- `GET /api/me`: current user dashboard payload, linked athlete profiles, latest
  performance profile, readiness, and metric catalog;
- `POST /api/me/athlete-profiles`: link an imported athlete to the current user;
- `DELETE /api/me/athlete-profiles/{id}`: unlink one of the current user's
  athlete profiles;
- `PUT /api/me/performance-profile`: create or update the current user's latest
  performance profile metrics;
- `GET /api/me/coach/clients`: list active coached clients owned by the current
  user;
- `POST /api/me/coach/clients`: create a coached client without requiring a
  MonWOD user account for that client;
- `GET /api/me/coach/clients/{id}`: read one owned coached client;
- `PATCH /api/me/coach/clients/{id}`: update one owned coached client;
- `DELETE /api/me/coach/clients/{id}`: archive one owned coached client;
- `POST /api/me/coach/clients/{id}/programming-generation-requests`: create an
  individual programming generation request for one owned coached client. The
  request body matches the personal programming endpoint shape:
  `{"type":"individual","constraints":{...}}`. The response is
  `{"programmingRequest": {...}}`, including `programmingRequest.coachedClient`
  and a frozen `programmingRequest.inputSnapshot.coach_client` context for the
  Python worker;
- `POST /api/me/performance-analysis-requests`: create a standalone personal
  performance analysis request. Analysis snapshots expose
  `analysisRequest.freshness.inputHash`, `freshness.version`,
  `freshness.freshnessWindowDays`, and source timestamps. The default freshness
  window is 30 days;
- `POST /api/me/programming-generation-requests`: create an individual
  programming generation request. The endpoint still accepts
  `{"type":"individual","constraints":{...}}`. If `constraints.sourceAnalysisRequestId`
  references a completed analysis that is fresh and compatible with the current
  athlete data snapshot, the programming request is created with
  `status: "queued"` and `analysisDependency.mode: "reused"`. If no fresh
  compatible analysis exists, the backend creates a new performance analysis
  request, returns the programming request with `status: "waiting_analysis"`,
  `analysisDependency.mode: "generated"`, and `sourceAnalysisRequest.status:
  "queued"`. When the source analysis completes, the programming request is
  moved to `queued` automatically and then dispatched to the programming worker;
- `GET /api/me/requests`: lists analysis requests, programming requests, and
  programming session detail requests. Programming requests may include
  `sourceAnalysisRequest` and `analysisDependency` to expose whether generation
  is waiting for analysis refresh;
- future saved or favorite WODs.

Deployment note: run Doctrine migrations before enabling this contract in
production so `programming_generation_request.source_analysis_request_id` exists.
No new environment variable or command is required; existing Messenger workers
and `app:ai-requests:enqueue` continue to dispatch queued AI work.

## Admin API

Admin endpoints require an administrator role.

Planned admin surface:

- product/crawler metrics dashboard;
- import and worker health;
- operational counters and failures.

## CORS

The committed default CORS policy allows local development and `https://www.monwod.fr`.

Production may override `CORS_ALLOW_ORIGIN` in `.env.local`.
