# Flutter API and screen map

The existing Flutter app should replace mock repositories with an HTTP client rooted at `{APP_URL}/api/v1`. Send `Accept: application/json`, `Authorization: Bearer {token}`, `X-Request-ID`, and an `Idempotency-Key` for offline mutations.

| Flutter concern | REST endpoint | Firebase path | Role | Refresh |
|---|---|---|---|---|
| Sign in | `POST /auth/login` | none | all | once/session |
| Forgot/reset password | `POST /auth/forgot-password`, `/auth/verify-otp`, `/auth/reset-password` | none | public | advance only after successful verification/reset |
| Profile | `GET /auth/me` | none | all | launch/profile change |
| Firebase bootstrap | `POST /firebase/session` | none | authenticated | token expiry/sign-in |
| Private attachments | `POST /files`, `GET|DELETE /files/{id}` | none | authorized resource member | after mutation |
| Notifications | `/notifications`, `/unread-count`, `/{id}/read`, `/read-all` | none | authenticated | launch + mutation |
| Reports/exports | `/reports/{type}`, CSV/print variants, `/reports/exports` | none | organization member | manual/status polling |
| Audit oversight | `/audit-logs`, `/{id}`, `/export` | none | ADMIN/INSPECTOR | filtered/manual |
| Boats | `/boats` | none | FISHER | pull-to-refresh |
| Fisher dashboard | `GET /fisher/dashboard` | none | FISHER | launch + mutation |
| Fisher form lookups | `GET /fisher/reference-data` | none | FISHER | launch + manual refresh |
| Fishing trips | `GET|POST /fishing-trips`, `GET|PUT|PATCH /fishing-trips/{id}`, and transition endpoints | none | FISHER | mutation + refresh |
| Catches | `GET|POST /catches`, `GET|PUT|PATCH|DELETE /catches/{id}` | none | FISHER | mutation + refresh |
| Batches | `/batches`, `/{id}/qr`, `/{id}/timeline`, `GET|POST /{id}/documents` | none | authorized supply roles; document upload FISHER | mutation + refresh |
| Processor dashboard/incoming | `/processor/dashboard`, `/processor/incoming-batches`, `/processor/history` | none | PROCESSOR | launch + mutation |
| Processor batch intake | `/processor/batches/{id}`, `/{id}/accept`, `/{id}/reject` | none | PROCESSOR | after scan/action |
| Processing records/steps | `POST /processing-records`, `GET|PUT /processing-records/{id}`, `/{id}/steps/{step}/start|complete` | none | PROCESSOR | after each transition |
| Quality inspection | `/quality-inspections` | none | PROCESSOR/INSPECTOR | after submission |
| Batch split/labels | `/batches/{id}/split`, `/{id}/children`, `/package-labels/{id}`, `/{id}/print` | none | PROCESSOR | after split |
| Retail dashboard/intake | `/retailer/dashboard`, `GET|POST /retailer/receipts`, or route-bound `/retail/package-labels/{id}/receive` | none | RETAILER | launch + scan/intake |
| Retail inventory | `/retailer/inventory[/{id}]`, `/retailer/stock-adjustments`; `/retail/inventory-lots/{id}` reserve/release/recall/expire | none | RETAILER | after each mutation |
| Retail sales | `GET|POST /retailer/sales`, `GET /retailer/sales/{id}` (`/retail/sales` remains compatible) | none | RETAILER | after checkout |
| Retail alerts/reports | `/retailer/alerts`, recall quarantine/resolve, `/retailer/reports/summary|sales|inventory` | none | RETAILER | launch + mutation/manual |
| Transport dashboard | `GET /transporter/dashboard` | none | TRANSPORTER | launch + mutation |
| Vehicles | `GET|POST /vehicles`, `GET|PUT|DELETE /vehicles/{id}` | none | TRANSPORTER | mutation + refresh |
| Transport list/detail | `GET|POST /transport-trips`, `GET|PUT /transport-trips/{id}` | none | TRANSPORTER | pull-to-refresh |
| Transport preparation | batch/device add/remove and `GET|POST /transport-trips/{id}/checklist` | assignment/member mirrors | TRANSPORTER | after each mutation |
| Transport execution | `/start`, `/incidents`, `/delivery-confirmation`, `/complete`, `/cancel` | assignment and live paths | TRANSPORTER | after each transition |
| Live transport | `GET /transport-trips/{id}/live-access` | `/liveTrips/{id}` | trip member | continuous listener |
| Sensor history | `/transport-trips/{id}/sensor-readings` (`date_from`, `date_to`, `page`, `per_page`), `/latest`, `/sensor-summary?period=hour\|day` | none | authorized | paginated/manual |
| Device lifecycle | `/iot/devices`, provision/rotate/disable/activate/deactivate, health/readings/latest-reading/firebase-sync-status | device assignment | ADMIN mutation; own TRANSPORTER read | manual/status refresh |
| AI decision support | `/batches/{id}/ai-predictions`, `/latest`, `/request` | none | authorized batch member | request/status refresh |
| Public trace | `/public/trace/{token}` | none | public | scan/manual |

All successful payloads use `{data, meta.request_id}`. Flutter should treat HTTP 409 as an authoritative workflow conflict, 422 as field validation, 401 as a revoked/expired Sanctum session, and 403 as role or organization denial.

Sensor reading JSON is privacy-whitelisted and never includes `raw_payload`. Use `reading_age_seconds` and `device_status` for freshness/health UI; the trip latest endpoint additionally provides `active_alert_count`.

AI prediction JSON is also whitelisted and always includes `decision_support` plus a disclaimer. Consumer blockchain status is aggregate evidence only; Flutter must not expect hashes or transaction references from the public endpoint.
