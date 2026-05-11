# Python Worker

Symfony delegates crawler, enrichment, analysis, and generation work to the Python service.

## Configuration

Set the Python worker base URL with:

```env
PYTHON_WORKER_BASE_URL=https://crawler.monwod.fr
```

For local development, the default is:

```env
PYTHON_WORKER_BASE_URL=http://127.0.0.1:8000
```

## Symfony Client

Use `App\Services\PythonWorker\PythonWorkerClientInterface`.

Current prepared calls:

- `submitPerformanceAnalysis()` posts to `/internal/performance-analysis`
- `submitProgrammingGeneration()` posts to `/internal/programming-generation`

Both calls send:

- the Symfony request ID;
- the user ID;
- optional target context, such as athlete profile, performance profile, or box;
- request parameters or constraints;
- a snapshot of the input data used for traceability.

## Existing Symfony Code To Move Later

`App\Services\Workout\WorkoutCreatorService` still builds a prompt and calls ChatGPT directly through `ChatGPTApiKeyInterface`.

That responsibility should move behind the Python worker once the worker endpoints exist. Symfony should keep storing the request and generated artifact; Python should run the AI generation.
