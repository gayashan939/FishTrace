# Administrator access management

FishTrace administrators can manage users, organizations, and inspect the fixed system roles from the web console or `/api/v1/admin/*`. Policies restrict every operation to users with the `ADMIN` role; authenticated non-administrators receive `403 Forbidden`.

## User invariants

- A user has one or more roles and one or more organizations, with exactly one assigned organization marked primary.
- Every role requires an active organization of the matching type: `ADMIN` maps to `REGULATOR`; all other role names map to the same organization type.
- Passwords must contain at least ten characters, mixed-case letters, and numbers. Passwords and Firebase identifiers are never returned by management resources.
- Updating roles or organizations, disabling an account, and resetting a password revoke Sanctum tokens, database sessions, and remember-me state.
- Administrators cannot disable themselves or remove their own administrator role. The final active administrator cannot be disabled or demoted.
- Locked accounts can be unlocked by resetting `failed_login_count` and `locked_until`.

User endpoints support search, status/role/organization/lock filters, whitelisted sorting, and pagination capped at 100 rows. Dedicated mutation endpoints activate, deactivate, unlock, revoke sessions, and reset passwords.

## Organization invariants

Organization codes are normalized to uppercase and are globally unique. Types are fixed to `REGULATOR`, `FISHER`, `PROCESSOR`, `TRANSPORTER`, `RETAILER`, or `INSPECTOR`. An organization type cannot change after users are assigned. An organization with active users cannot be deactivated; those users must first be disabled or reassigned.

Organization endpoints support search, type/status filters, whitelisted sorting, and bounded pagination. Roles are fixed reference data and are read-only through management interfaces.

## Audit trail

User access assignment, access updates, status changes, unlocks, session revocations, administrator password resets, organization creation/update, and organization status changes are recorded in the immutable audit log. Pivot changes are captured explicitly so role and organization ID sets remain traceable even though they are not standalone models.
