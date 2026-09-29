# Studio Hata WordPress

Version-controlled WordPress foundation for [studiohata.nl](https://studiohata.nl).

The repository intentionally tracks only code we own: the Studio Hata theme,
future custom plugins, infrastructure configuration, and documentation. WordPress
core, third-party plugins, uploads, databases, and secrets stay outside Git.

## Project structure

```text
.
├── .github/workflows/quality.yml  # Safe checks on pushes and pull requests
├── compose.yaml                   # Optional portable local environment
├── docs/environments.md           # Local, staging, and production policy
└── wp-content/
    ├── mu-plugins/                # Always-on custom functionality
    ├── plugins/                   # Third-party plugins; ignored by Git
    └── themes/studio-hata/        # The custom Studio Hata block theme
```

## Local development with Docker

Docker is not currently installed on the machine where this repository was
created. The configuration is included so any developer can use the same setup
once Docker Desktop, OrbStack, or another Compose-compatible runtime is present.

1. Copy `.env.example` to `.env` and replace the local-only passwords.
2. Run `docker compose up -d`.
3. Open `http://localhost:8080` (or the port set in `.env`).
4. Complete the WordPress installer and activate **Studio Hata**.

Stop the environment with `docker compose down`. Add `--volumes` only when you
intentionally want to delete the local database and WordPress installation.

## LocalWP alternative

Create a site named `studio-hata`, then point or copy its `app/public/wp-content`
directory to this repository's `wp-content`. Match PHP 8.3 and set the WordPress
environment type to `local`. Do not commit LocalWP-generated configuration,
database files, logs, uploads, or SSL material.

Use one local approach per developer. Docker is the shared baseline because its
configuration is reviewable; LocalWP is a convenience, not a second source of
truth.

## Environments

- **Local:** disposable developer data; debugging on; local credentials only.
- **Staging:** private or no-index test site with production-like hosting;
  separate database, media, API keys, analytics property, and email sink.
- **Production:** `studiohata.nl`; debugging off; protected credentials;
  deployment only from an approved release.

See [docs/environments.md](docs/environments.md) for promotion and data-handling
rules.

## Branch and release strategy

`main` is always deployable. Work happens on short-lived branches named by
purpose, such as `feature/booking-flow` or `fix/mobile-navigation`, and lands via
pull request after the quality workflow passes. Avoid a permanent `develop`
branch; staging should reflect `main`, which removes an extra source of drift.

Once hosting is selected:

- merge to `main` -> deploy custom code to staging;
- create an annotated `vX.Y.Z` tag -> require approval, then deploy the same
  commit to production;
- use the host's atomic releases or deploy into a new release directory before
  switching the active symlink;
- exclude the database and uploads from code deployments.

No deployment workflow is enabled yet because the target host and its supported
authentication method have not been chosen. That prevents an accidental or
misleading production setup.

## Secrets

- Keep local values only in `.env`; the file is ignored by Git.
- Store staging and production secrets in the hosting platform's secret store.
- Give CI a narrowly scoped deploy credential for each environment; never reuse
  an administrator password or a production database password.
- Prefer short-lived SSH or provider-issued tokens. Rotate any credential that
  is pasted into a ticket, chat, commit, or log.
- WordPress salts belong in environment configuration, not this repository.

## Content and plugins

Content, users, bookings, and settings live in the database. Media lives in the
uploads directory or object storage. Third-party plugins are installed and
updated through a controlled environment process and recorded in documentation;
their source is not committed. Custom business logic belongs in a custom plugin
or `mu-plugins`, not in the theme.

Before production launch, add backups, uptime monitoring, transactional email,
security hardening, consent/analytics, and a tested staging-to-production release
workflow based on the chosen host.

