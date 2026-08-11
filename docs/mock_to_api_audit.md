# Mock-to-API audit

Audit updated: 2026-08-09

## Existing architecture

- Routing is `go_router`, not GetX routing. It already provides authenticated and
  cross-role redirects for all four role prefixes, so it will be preserved.
- Presentation state uses six GetX controllers: `AppController`,
  `AuthenticationController`, `CommonController`, `FisherController`,
  `ProcessorController`, `TransporterController`, and `RetailerController`.
- `InitialBinding` and role bindings now own GetX dependency selection. The
  project intentionally retains its existing `go_router` routes instead of
  replacing working navigation with GetX pages.
- Backend-neutral repository interfaces now have mock and Laravel implementations
  for the active Fisher, Processor, Transporter, Retailer, common, file, auth,
  and live-sensor workflows.
- Fisher uses typed Drift tables plus an offline-first repository and durable
  sync queue carrying endpoint, HTTP method, client record ID, serialized
  payload, and local file references.
- Live monitoring now uses Firebase custom-session authentication and an
  authorized Realtime Database `/liveTrips/{tripId}` listener in API mode;
  mock mode retains its local stream.

## Controllers and lifecycle

| Controller | Repository boundary | Owned resources | Audit result |
|---|---|---|---|
| AppController | Auth, offline, sync, sensor | connectivity and sensor subscriptions | cancellation present |
| AuthenticationController | AppController, onboarding | OTP timer | cancelled in `onClose` |
| CommonController | CommonRepository | none | no leak found |
| FisherController | FisherRepository | none | no leak found |
| ProcessorController | ProcessorRepository | none | no leak found |
| TransporterController | TransporterRepository | delegates sensor lifecycle | explicit start/stop present |
| RetailerController | RetailerRepository | none | no leak found |

Controllers expose typed application failures through `AppException`; no
controller passes raw Dio or Firebase failures to views. Form-specific
submission state remains feature-owned where the existing UI needs it.

## Routing and bindings

- All 40 primary screens use explicit `go_router` paths.
- Role protection is enforced by router redirects, not merely hidden navigation.
- Initial and role bindings select exactly one mock or API implementation for
  each interface. `go_router` continues to own the existing authenticated and
  role-aware redirects.

## Data and hardcoding

- Mock domain records are correctly located in mock repositories.
- A small amount of form seed/display data remains in controllers and screens
  (inspection criteria, crew choices, weather/map placeholders, and demo codes).
  These are presentation defaults or reference placeholders, not API records.
- Views do not call Dio or Firebase directly.
- Controllers do not instantiate repositories or call Dio/Firebase directly.
- DTO mappers, `ApiData`, `ErrorMapper`, and endpoint-aware sync serialize the
  Laravel snake-case contract without exposing JSON maps to views.

## Missing integration pieces

- Retail reports still render placeholder chart series; the existing report API
  endpoints are not yet exposed through a report repository/controller.
- Final field-level audits remain for report payloads, notifications, AI,
  blockchain, and some IoT nullable telemetry fields.

## Endpoint coverage required

- Common/auth: `/auth/*`, `/firebase/session`, `/notifications`, and `/files`.
  Profile edits, bundled help, and support acknowledgement remain local because
  the published Laravel mobile contract does not expose those mutations.
- Fisher: `/fisher/dashboard`, `/boats`, `/fishing-trips`, `/catches`, and
  `/batches` including QR, timeline, and documents.
- Processor: `/processor/*`, `/processing-records`, `/quality-inspections`, and
  batch accept/reject/split/children endpoints.
- Transporter: `/transporter/dashboard`, `/vehicles`, `/transport-trips`,
  checklist/device/batch/actions, sensor REST endpoints, alerts, incidents, and
  delivery confirmation.
- Retailer: `/retailer/dashboard`, receipts, inventory, stock adjustments,
  sales, alerts, recalls, and reports.
- Firebase live path: `/liveTrips/{tripId}` after Laravel-authorized live access.

## Risks and assumptions

- Runtime certification still needs production Laravel credentials, Firebase
  Android/iOS configuration, custom-token authentication, and RTDB rules.
- The prompt mentions GetX routing only when already configured. This project
  already uses `go_router`; replacing it would risk the completed navigation and
  is intentionally out of scope.
- Existing UI composition and mock mode must remain unchanged.
