# Navigation map

`/splash` → `/onboarding` → `/login`; authentication redirects to `/fisher`,
`/processor`, `/transporter`, or `/retailer`. All 40 primary screens have named routes
in `AppRoute`. Protected workflow paths validate their role prefix before displaying.
Bottom navigation preserves the dashboard, workflow list, primary action, alerts/history
and More destinations per role.
