---
paths:
  - 'app/Features/**'
---

# Features

## Feature safety gates precede preferences and overrides
FeatureEvaluator is the shared backend/Inertia/Pennant evaluator. Kill switch, draft/archive, environment, schedule, beta consent, and role denies must beat user overrides. Keep enrollment distinct from user preferences and admin overrides. Pennant FeatureUpdated events must flush its optimistic cache so activate() cannot bypass safety gates in the same request. Evaluation caches must remain request-scoped for Octane.
