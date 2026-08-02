# Assumptions

- Laravel 13 and PHP 8.3+ are acceptable to the target host; verify cPanel's PHP version before deployment.
- All timestamps are stored and exchanged in UTC; presentation may convert to Asia/Colombo.
- One primary organization is used for operational API scoping in the first milestone, while the schema permits multiple memberships.
- `client_record_id` is a UUID unique within an organization. Server workflow state wins during conflicts.
- The mock Firebase token is deliberately not accepted by real Firebase; it is for local contract testing only.
- Demo credentials and seeded provisioning identities are development data and must not be seeded in production.
- Exact coordinates are operational/private and never returned by the consumer trace API.
- The implementation checklist is authoritative about incomplete advanced phases; scaffolded configuration is not treated as a finished integration.
