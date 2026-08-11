# Contract assumptions

## Boat engine field

The active Flutter form exposes one required free-text field labelled **Engine
details**. The canonical storage and JSON key is therefore `engine_details`,
not an invented set of brand/model/power fields. A later structured engine
workflow requires a separately versioned migration and UI change.

## Existing boats

The additive migration leaves the three new columns nullable so previously
stored boats remain readable. New mobile create/update requests require
`length_meters`, `engine_details`, and `home_port`; old records must be
backfilled before an administrative edit flow is made mandatory.

## Broader audit scope

The repositories contain implemented modules beyond the Boat reference path.
Their API and mock DTOs are listed as follow-up audit work rather than being
marked reconciled without an end-to-end field comparison.

## Trip code and departure timestamps

The visible, required Flutter Trip Code remains the public `trip_code`. The
offline queue UUID is stored separately as `client_record_id` for idempotency.
The user-selected time is `planned_departure_at`; `departed_at` remains the
server-owned time recorded by the start transition.

## Batch landing site

The current Flutter form is a free-text field rather than a reference-data
selector. It is therefore persisted as `landing_site_name`. Converting it to a
`landing_site_id` foreign key requires a UI workflow change and is deferred.

## Processor step workflow

Laravel's typed processing-step state machine remains authoritative. The
Flutter screen now captures the required per-step measurements and submits
ordered start/complete mutations; the sync layer does not invent defaults.

## Mobile profile image

The profile camera glyph is not an interactive picker and neither the users
table nor `UserResource` defines an avatar. No avatar field was inferred from a
decorative control. Avatar persistence will be added only if the file audit
finds an active upload workflow or explicit business requirement.

## Mobile settings ownership

Profile preferences, measurement display choice, language preference, and
local-notification choices are device settings rather than traceability or
organization records. They are stored under one versioned, typed secure-storage
key shared by mock and API modes. No Laravel columns or endpoint were inferred.
API payloads and persisted business values remain in canonical metric units.

## AI mobile boundary and missing telemetry

No active Flutter screen, route, repository, controller, or form requests an AI
prediction or displays prediction history. The published Laravel endpoints are
therefore retained as backend/admin integration contracts; no speculative
Flutter AI workflow was added. Missing sensor measurements remain `null` in AI
features and are accompanied by telemetry presence/count fields. They are not
converted to `0`, which would falsely describe absent evidence as a valid cold
temperature.
