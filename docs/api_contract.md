# API contract

API mode uses Laravel Sanctum bearer-authenticated JSON under `/api/v1`.
Mutations accept an
`Idempotency-Key` header and return a stable server ID. Errors use
`{"error":{"code":"machine_code","message":"Readable explanation","field_errors":{}}}`.

| Area | Endpoints |
|---|---|
| Authentication | login, me, logout/logout-all, forgot-password, verify-otp, reset/change-password under `/auth/*`; `POST /firebase/session` |
| Fisher | `GET /fisher/dashboard`; CRUD `/boats`, `/fishing-trips`, `/catches`; trip start/complete/cancel |
| Fisher batches | `GET/POST /batches`, details, QR, timeline, and documents |
| Processor | dashboard, incoming-batches, history, batch details and accept/reject under `/processor/*` |
| Processing | CRUD `/processing-records`, step start/complete, `/quality-inspections`, batch split/children |
| Transport | `GET /transporter/dashboard`; CRUD `/vehicles` and `/transport-trips`; batch/device/checklist/start/complete/cancel actions |
| Sensors | trip readings, latest, summary, alerts, and live-access under `/transport-trips/{id}` |
| Firebase live | Authorized Realtime Database path `/liveTrips/{tripId}`; permanent history never comes from Firebase |
| Delivery/incidents | `POST /transport-trips/{id}/incidents`, `POST /transport-trips/{id}/delivery-confirmation` |
| Retail | dashboard, receipts, inventory, stock-adjustments, sales/details, alerts, quarantine/resolve, and report endpoints under `/retailer/*` |
| Notifications | `GET /notifications`, `POST /notifications/{id}/read` |
| Support | No Laravel mobile endpoints; bundled help and local acknowledgement |
| Files | multipart `POST /files` with progress and cancellation |
| Offline replay | The queue resolves the domain endpoint and sends `client_record_id`, original UTC timestamps and `Idempotency-Key` |

`POST /auth/login` accepts `email`, `password`, and `device_name`, and returns a
token and user. Password verification returns the opaque `reset_token` required
by reset-password. The client exchanges the Sanctum-authenticated session at
`POST /firebase/session` for a Firebase custom token. Repeated mutation idempotency keys
must return the original result without creating duplicates.
