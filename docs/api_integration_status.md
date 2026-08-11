# Laravel and Firebase integration status

Audit date: 2026-08-03

Environment selection is centralized in `AppConfig` and `InitialBinding`.
`DATA_SOURCE_MODE=mock` registers only mock implementations;
`DATA_SOURCE_MODE=api` registers only Laravel implementations. Existing
`go_router` navigation remains unchanged and role protected.

Laravel paths are centralized in `ApiEndpoints`. The role aggregates preserve
the completed screen state while GetX also exposes the narrower requested
contracts: notification/support, boat/trip/catch/batch, incoming/processing/
inspection, transport-trip/vehicle/device/delivery, inventory/receipt/sale/
alert, file, sensor/live-sensor, profile, authentication and sync. All API list
responses pass through feature DTO mappers before reaching domain state.

| Feature/screens | GetX controller | Binding | Repository interface | Mock implementation | API/Firebase implementation | Laravel endpoints / Firebase path | DTO and mapping | Offline behavior | Tests |
|---|---|---|---|---|---|---|---|---|---|
| Login/session/recovery | AuthenticationController, AppController | AuthenticationBinding | AuthRepository, FirebaseSessionRepository | MockAuthRepository, MockFirebaseSessionRepository | ApiAuthRepository, ApiFirebaseSessionRepository | `/auth/login`, `/auth/me`, logout/logout-all, forgot-password, verify-otp, reset/change-password, `/firebase/session` | snake_case envelope mapped to User; safe role parser | secure token and safe auth metadata | login, token, restoration |
| Profile/settings | AppController | CommonBinding | ProfileRepository | MockProfileRepository | ApiProfileRepository | `/auth/me`, `/auth/change-password`; profile edits local until a backend update route exists | User mapping through auth repository | current edit retained locally | binding compilation |
| Notifications/help | CommonController | CommonBinding | CommonRepository | MockCommonRepository | DioCommonRepository | `/notifications`, read; bundled help/support fallback | nested notification mapper | help/support remain local | 40-screen regression |
| Uploads for all roles | UploadController | FileBinding | FileRepository | MockFileRepository | ApiFileRepository | multipart `POST /files` with category/entity metadata | UploadedFile | local paths stay in drafts/queue and upload after owner sync | progress and mock upload |
| Fisher dashboard/boats/trips/catches/batches | FisherController | FisherBinding | FisherRepository plus Boat/FishingTrip/Catch/BatchRepository, FisherDraftStore | MockFisherRepository | DioFisherRepository wrapped by OfflineFirstFisherRepository | `/boats`, `/fishing-trips`, `/catches`, `/batches`; mutation queue resolves start/complete endpoints | snake_case Fisher DTOs, safe enums, UTC timestamps, paginated boat response | typed Drift boats/trips/catches/photos/batches; local fallback | offline domain, pagination, DTO and sync tests |
| Processor dashboard/intake/workflow/inspection/split/history | ProcessorController | ProcessorBinding | ProcessorRepository plus IncomingBatch/Processing/QualityInspectionRepository | MockProcessorRepository | DioProcessorRepository | `/processor/incoming-batches`, `/processor/history`, accept/reject, `/processing-records`, `/quality-inspections`, batch split | snake_case Processor DTOs and safe enums | durable endpoint-aware mutation queue | accept/reject and workflow regression |
| Transport trips/vehicles/devices/checklist/delivery | TransporterController | TransporterBinding | TransporterRepository plus TransportTrip/Vehicle/IoTDevice/DeliveryRepository | MockTransporterRepository | DioTransporterRepository | `/transport-trips`, `/vehicles`, `/iot/devices`, batch/device/checklist/delivery endpoints | snake_case Transporter DTOs and safe enums | endpoint-aware queue with local file refs | device/checklist/delivery regression |
| Live monitoring | LiveMonitoringController | LiveMonitoringBinding | SensorRepository, LiveSensorRepository | MockSensorRepository, MockLiveSensorRepository | LaravelSensorRepository, FirebaseLiveSensorRepository | REST readings/latest/history/live-access; RTDB `/liveTrips/{tripId}` | Firebase snapshots mapped to SensorReading; views never see snapshots | REST latest fallback; bounded 100-sample memory | stream, bound, fallback, disposal |
| Retail dashboard/receipts/inventory/sales/alerts/reports | RetailerController | RetailerBinding | RetailerRepository plus Receipt/Inventory/Sale/RetailAlertRepository | MockRetailerRepository | DioRetailerRepository | `/retailer/receipts`, inventory, sales, alerts; stock adjustment and quarantine mutation endpoints | snake_case Retail DTOs, safe enums, UTC timestamps | endpoint-aware durable queue | receipt, sale, stock, recall regression |
| Sync status | SyncController, AppController | InitialBinding | OfflineRepository, SyncTransport | Memory/Drift and MockSyncTransport | DriftOfflineRepository and DioSyncTransport | resolved Laravel endpoint per operation with `Idempotency-Key` | serialized payload plus operation metadata | pending/syncing/synced/failed, bounded backoff, reconnect | persistence, retry, endpoint/idempotency |

## Network and lifecycle status

- Dio has the configured `/api/v1` base URL, JSON headers, timeouts, request IDs,
  bearer injection, safe debug logging, session-expiry handling, multipart and
  cancellation support.
- `ApiClient` maps Dio failures through `ErrorMapper`; controllers and views do
  not receive raw Dio or Firebase exceptions.
- Search debounce is owned and disposed by `FisherController`; OTP timers,
  connectivity streams, Firebase listeners, sensor streams, stale timers, and
  upload cancellation tokens all have explicit cleanup.
- Session restoration calls `/auth/me`, recreates the Firebase custom-token
  session, restores role state and leaves `go_router` to redirect to the correct
  dashboard. Cross-role redirects remain enforced client-side; Laravel remains
  the final authorization authority.

## Verification

- `dart format --set-exit-if-changed .` — pass
- `flutter analyze` — pass, no issues
- `flutter test` — pass, 78 tests
- Mock Android APK with `DATA_SOURCE_MODE=mock` — pass
- API Android APK with Laravel URL and `FIREBASE_ENABLED=true` — pass

## Backend deployment dependencies

Runtime API certification still needs the backend quality-grade reference
contract described in `docs/live_api_audit.md`, Firebase Android/iOS configuration, enabled Firebase
Authentication custom tokens, Realtime Database rules authorizing only the
Laravel-issued `/liveTrips/{tripId}` path, and production credentials. Unit
tests intentionally use mocked Dio and live repositories and require no external
service.

Authenticated live REST results and the remaining backend/Firebase blockers are
recorded in `docs/live_api_audit.md`.
