# Studio Hata WordPress

Version-controlled WordPress foundation for [studiohata.nl](https://studiohata.nl).

The repository intentionally tracks only code we own: the Studio Hata theme,
future custom plugins, infrastructure configuration, and documentation. WordPress
core, third-party plugins, uploads, databases, and secrets stay outside Git.

## Project structure

```text
.
├── .github/workflows/quality.yml  # Safe checks on pushes and pull requests
├── docs/local-development.md      # LocalWP setup and daily workflow
├── docs/environments.md           # Local, staging, and production policy
├── docs/polylang.md               # Reproducible multilingual configuration
├── tools/bootstrap/hata-a/         # Idempotent HATA A bootstrap and draft copy
├── tools/seed/                     # Imported HATA B content tooling
└── wp-content/
    ├── mu-plugins/hata-core.php   # Environment guards and structured data
    ├── plugins/                   # Third-party plugins; ignored by Git
    └── themes/
        ├── studio-hata/           # HATA A: custom block theme from scratch
        └── hata-kadence/          # HATA B: Kadence child theme
```

## Local development with LocalWP

LocalWP is the local runtime for this project. The Git clone remains the primary
working copy. HATA A links to the custom `studio-hata` block theme; HATA B links
to the `hata-kadence` child theme and installs the free Kadence parent theme
inside LocalWP. Both variants link to the shared must-use plugin. WordPress core,
the database, uploads, and third-party plugins remain managed by LocalWP and
outside Git.

See [docs/local-development.md](docs/local-development.md) for the one-time setup
and daily workflow. Docker is not part of the active development setup.

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
or `mu-plugins`, not in the theme. The files under `tools/seed` are development
tools for generating reviewable draft pages; they are not deployed as runtime
WordPress code.

Before production launch, add backups, uptime monitoring, transactional email,
security hardening, consent/analytics, and a tested staging-to-production release
workflow based on the chosen host.
