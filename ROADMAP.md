# Roadmap

## Goal

Symfony becomes the canonical product backend and database.

Python remains responsible for crawling, parsing, AI enrichment, AI generation, and long-running jobs. It should not remain a separate product database for workouts, athletes, competitions, or results.

## Target Data Ownership

Symfony owns:

- workouts
- athletes
- competitions
- events
- workout results
- scores and rankings
- user accounts
- boxes and memberships
- saved workouts
- programming
- workout collections
- product permissions

Python owns:

- crawlers
- raw crawl snapshots
- parsing and normalization jobs
- AI analysis
- AI generation
- temporary caches
- import/export scripts
- job logs

## Phase 1: Stabilize Symfony As The Canonical Database

- Keep `Workout` as the unified workout entity.
- Use plain text `flow` as the canonical workout body.
- Keep movements, implements, muscles, body parts, movement types, difficulties, workout types, and origins as enrichment metadata.
- Add product entities for imported data:
  - `Athlete`
  - `Competition`
  - `CompetitionEvent`
  - `WorkoutResult`
  - `Score`
- Keep source metadata on imported records so every row can be traced back to CrossFit Games, CompetitionCorner, ScoringFit, or another source.

## Phase 2: Define The Python To Symfony Import Contract

- Define stable JSON payloads for:
  - workouts
  - athletes
  - competitions
  - events
  - results
  - rankings
- Add idempotent Symfony import endpoints or console commands.
- Store external IDs and source names to avoid duplicate imports.
- Normalize score units at import time:
  - reps
  - time
  - load
  - distance
  - calories
  - points
  - raw text fallback

## Phase 3: Migrate Existing Python Data

- Export the current Python database into a versioned JSON format.
- Import workouts into Symfony `Workout`.
- Import athletes into Symfony `Athlete`.
- Import competitions and events.
- Import results and scores.
- Verify counts before and after migration.
- Keep a migration report for duplicates, rejected rows, and uncertain matches.

## Phase 4: Make Python A Worker Service

- Python keeps crawling external sources.
- Python sends normalized data to Symfony instead of becoming the product database.
- Python can keep raw crawl snapshots for debugging and replay.
- Symfony stores the official product data consumed by the frontend.
- Long-running jobs should be triggered explicitly and tracked.

## Phase 5: Rebuild Tests Properly

- Replace legacy tests with tests around actual product behavior.
- Add import idempotency tests.
- Add score normalization tests.
- Add athlete matching tests.
- Add workout matching tests.
- Add API tests for frontend workflows.
- Add fixture loading tests because fixtures are product data, not disposable test scaffolding.

## Near-Term Next Steps

1. Model `Athlete`, `Competition`, `CompetitionEvent`, `WorkoutResult`, and `Score` in Symfony.
2. Add migrations and minimal repositories.
3. Create a small import command that reads a JSON file exported by Python.
4. Export a small sample from Python.
5. Import that sample into Symfony test database.
6. Iterate on the schema until the imported data feels natural.
7. Only then migrate the full Python data set.
