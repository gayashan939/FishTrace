# Firebase assets

Install Java 21 or newer, then from the repository root run `npm run test:firebase-rules` for the disposable automated rules suite. For interactive local work, run `firebase emulators:start --config firebase/firebase.json --project demo-fishtrace`. Deploy reviewed rules with `firebase deploy --only database --config firebase/firebase.json --project PROJECT_ID` for an approved project. The Admin SDK credential belongs outside this repository and outside the web root. Review `docs/firebase-security-rules.md` before deployment.
