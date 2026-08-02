# Offline synchronization

Mobile create requests should include a UUID `client_record_id`, client timestamps, and `Idempotency-Key`. Catch creation currently enforces per-organization client ID idempotency and returns the original record on replay. Later modules must use the same pattern with a permanent idempotency table, request fingerprint, stored response, expiry, and 409 on key reuse with a different payload.
