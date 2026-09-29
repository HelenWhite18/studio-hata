# Environment strategy

## Isolation

Local, staging, and production must use different databases, credentials,
uploads, email destinations, payment modes, analytics properties, and booking
integrations. Staging should be password-protected where possible and must send
`noindex` headers or enable WordPress's search-engine visibility setting.

| Concern | Local | Staging | Production |
| --- | --- | --- | --- |
| URL | `localhost` | host-provided or `staging.studiohata.nl` | `studiohata.nl` |
| Debugging | enabled, logged | logged, not displayed | disabled |
| Email | captured or disabled | test recipients only | live provider |
| Payments | sandbox | sandbox | live keys |
| Data | synthetic | sanitized copy if necessary | authoritative |
| Deployment | working tree | `main` | approved release tag |

## Promotion

Promote code forward: local -> pull request -> `main`/staging -> tagged production
release. Do not routinely copy the staging database into production. If content
must move, use an explicit export/import plan for the affected content types.

Production data may move backward only after personal data and secrets are
removed. A database copy is never part of an ordinary code deployment.

## Configuration

Use `WP_ENVIRONMENT_TYPE` with `local`, `staging`, or `production`. Keep URLs,
database access, salts, mail credentials, payment keys, and other environment
values in the host's configuration or secret store. Environment-specific values
must not be hard-coded into the theme.

Production and staging should prevent dashboard code editing and plugin/theme
installation. Changes are made in Git, reviewed, and deployed as an immutable
release. Emergency dashboard changes must be reproduced in Git immediately.

## Backups and rollback

Before the first launch, configure automated database and uploads backups with a
documented retention period and an off-host copy. Test restoration on staging.

Every deployment should retain the previous code release for rapid rollback.
Database migrations must be backward-compatible with at least the previous code
release or include a tested restore procedure.

