# Administrator operations dashboard

The administrator dashboard at `/admin` is a bounded, cross-module operational read model backed only by permanent MySQL/MariaDB records.

## Dashboard coverage

- Supply-chain KPIs cover active users and organizations, traceable batches, active fishing and transport, and processing holds.
- Risk KPIs cover failed/conditional inspections, open cold-chain alerts, recalled and expiring inventory, recent high AI risk, and unresolved Firebase synchronization failures.
- Retail summaries reconcile available, reserved, and sold packages plus sales recorded today.
- Attention queues show the newest five quality incidents, cold-chain alerts, inventory risks, unhealthy devices, and report jobs requiring attention.
- Recent batches are capped at eight and permanent telemetry is capped at ten readings.
- Every KPI and queue entry links to the relevant filtered directory or authoritative detail page.

## Performance and privacy

Every collection is bounded and relations are eager loaded. The feature suite enforces a maximum of 50 database queries for the fully rendered dashboard.

Telemetry selects only environmental and device-health fields. Coordinates, raw payloads, Firebase identifiers and failure bodies, report filters/failure messages, AI features, and other integration secrets are excluded.
