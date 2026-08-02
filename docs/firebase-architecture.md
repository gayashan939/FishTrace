# Firebase architecture

Laravel is authoritative for users, trips, batches, devices, and assignments. `FIREBASE_DRIVER=mock` stores a hierarchical RTDB facsimile in Laravel cache; `admin` uses the Kreait Admin SDK and credentials from `FIREBASE_CREDENTIALS`, preferably an absolute path outside the public web root.

Paths:

- `/deviceAssignments/{deviceFirebaseUid}`: Laravel-written active assignment and batch allowlist.
- `/tripMembers/{transportTripUuid}/{userFirebaseUid}`: Laravel-written read allowlist.
- `/liveTrips/{transportTripUuid}`: latest device state for Flutter listeners.
- `/telemetry/{deviceFirebaseUid}/{messageUuid}`: immutable device-created telemetry awaiting MySQL import.

Flutter signs into Laravel first, calls `POST /api/v1/firebase/session`, then passes `firebase_custom_token` to `signInWithCustomToken`. Claims describe the identity, but access is still checked against `tripMembers`.

`fishtrace:sync-firebase-telemetry` validates device identity, active assignment, payload ranges, and unique `message_id`, then stores the permanent reading and marks the Firebase record synchronized. `fishtrace:cleanup-firebase-telemetry` removes only synchronized records older than retention.
