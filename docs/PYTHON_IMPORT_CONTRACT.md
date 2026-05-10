# Python To Symfony Import Contract

## Purpose

Python crawlers and AI jobs send normalized data to Symfony. Symfony is the canonical product database.

This contract defines the first stable payload shape for imported competition data:

- workouts
- athletes
- competitions
- events
- results
- scores

The first supported contract version is `competition-results.v1`.

## Import Rules

All imported records must carry source metadata:

- `source.name`: stable source identifier such as `crossfit_games`, `competition_corner`, or `scoringfit`
- `source.externalId`: source-specific stable ID
- `source.url`: optional source URL

Symfony imports must be idempotent. Re-importing the same payload must update existing records, not create duplicates.

The natural identity of imported records is:

```text
source.name + source.externalId
```

## Payload Envelope

```json
{
  "contractVersion": "competition-results.v1",
  "generatedAt": "2026-05-10T12:00:00+00:00",
  "source": {
    "name": "crossfit_games",
    "url": "https://games.crossfit.com"
  },
  "workouts": [],
  "athletes": [],
  "competitions": [],
  "events": [],
  "results": []
}
```

## Workouts

Imported workouts become Symfony `Workout` records.

Required fields:

- `source.name`
- `source.externalId`
- `name`
- `flow`

Optional fields:

- `source.url`
- `timeCap`
- `workoutType`
- `originName`
- `originYear`
- `movementNames`
- `implementNames`

## Athletes

Imported athletes become Symfony `Athlete` records.

Required fields:

- `source.name`
- `source.externalId`
- `displayName`

Optional fields:

- `source.url`
- `firstName`
- `lastName`
- `gender`
- `country`

## Competitions

Imported competitions become Symfony `Competition` records.

Required fields:

- `source.name`
- `source.externalId`
- `name`

Optional fields:

- `source.url`
- `season`

## Events

Imported events become Symfony `CompetitionEvent` records.

Required fields:

- `source.name`
- `source.externalId`
- `competitionSourceId`
- `name`

Optional fields:

- `source.url`
- `workoutSourceId`
- `eventOrder`

`competitionSourceId` and `workoutSourceId` refer to source external IDs in the same payload or already imported records.

## Results

Imported results become Symfony `WorkoutResult` records.

Required fields:

- `source.name`
- `source.externalId`
- `athleteSourceId`
- `eventSourceId`
- `score`

Optional fields:

- `source.url`
- `rank`
- `division`
- `points`

## Score Normalization

Supported score types:

- `reps`
- `time`
- `load`
- `distance`
- `calories`
- `points`
- `raw`

Score fields:

- `type`: one of the supported score types
- `rawValue`: exact value from source, required
- `displayValue`: user-facing score, optional
- `numericValue`: normalized number when applicable, optional
- `timeInSeconds`: normalized time when applicable, optional
- `unit`: normalized unit such as `reps`, `lb`, `kg`, `m`, `cal`, optional

If the crawler cannot confidently normalize the score, it must use:

```json
{
  "type": "raw",
  "rawValue": "source value"
}
```

## Import Order

Symfony importers should process payload sections in this order:

1. workouts
2. athletes
3. competitions
4. events
5. results

This allows results to reference already imported athletes and events.

## Error Handling

Import commands should:

- reject unsupported `contractVersion`
- report missing required fields
- report unresolved references
- continue importing valid rows when possible
- produce a summary with created, updated, skipped, and failed counts

## Compatibility

Fields may be added in later `competition-results.v1` payloads if they are optional.

Breaking changes require a new contract version.

## Example

See [competition-results.v1.json](../examples/import/competition-results.v1.json).
