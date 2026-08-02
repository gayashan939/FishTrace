# Authorization

Policies enforce both role capability and organization ownership. FISHER owns boat, fishing-trip, catch, and batch creation. TRANSPORTER owns transport operations in its primary organization. IoT provisioning is ADMIN-only. Supply-chain roles may read a batch only under the explicit batch policy; public consumers use a separate whitelist serializer and never authenticate against Firebase.

Route authentication alone is insufficient. Every newly protected model requires a policy, and queries must also scope collections by organization to prevent enumeration.

Administrator access management has explicit `User`, `Organization`, and `Role` policies. Roles are read-only reference data. User mutations additionally enforce compatible active organization assignments, one primary organization, session revocation after access-sensitive changes, and active-administrator continuity. Organization mutations prevent type changes after membership and prevent deactivation while active users remain assigned.
