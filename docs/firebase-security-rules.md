# Firebase Security Rules

Deploy `firebase/database.rules.json`. The root denies reads and writes. A Flutter user reads `/liveTrips/{tripId}` only when `/tripMembers/{tripId}/{auth.uid}` is true. A device writes only the live trip in its active `/deviceAssignments/{auth.uid}` record and may create, never overwrite, telemetry beneath its own UID.

Laravel Admin SDK operations bypass client rules and are responsible for `deviceAssignments`, `tripMembers`, and sync metadata. Test rules with the Firebase Emulator before production deployment. Never relax root rules to support debugging.
