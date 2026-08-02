# Cold-chain alert rules

Administrators manage global rules at `/admin/settings/alert-rules`. Changes are validated, applied transactionally, and recorded as `ALERT_RULE_UPDATED` audit events. Global defaults apply to every transporter; the resolver also supports organization-specific records when a future operational requirement introduces overrides.

| Rule | Default | Behavior |
|---|---:|---|
| High temperature | warning above 4°C for 10 minutes; critical at 8°C | Sustained warnings and immediate critical alerts |
| Low temperature | below 0°C | Warning alert |
| Low battery | warning below 20%; critical below 10% | Warning or critical alert |
| Device offline | 15 minutes | Scheduled alert and deduplicated notification |
| GPS unavailable | immediate | Warning when latitude or longitude is absent |
| Door opened | immediate | Warning while the cargo door is reported open |

Disabling a rule resolves its unresolved alerts when the evaluator or offline monitor next runs. Recovered readings also resolve open or acknowledged alerts. Critical conditions create privacy-safe traceability milestones, and alert acknowledgement is transactional and audited.

The scheduler runs `php artisan fishtrace:check-offline-devices` every five minutes. Its optional `--minutes` argument is intended only for controlled diagnostics; normal scheduled execution uses the configured rule.
