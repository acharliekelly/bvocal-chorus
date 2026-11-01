# BVOCAL WordPress customizations

This repository contains the custom code for the [BVOCAL](https://bvocalchorus.org/)
WordPress site. It is intentionally **not** a full website backup or a deployment
package.

## What belongs here

- `themes/bvocal/` — BVOCAL's custom block theme.
- `plugins/bvocal-gig-rsvp/` — per-Gig RSVP forms and MailerLite-group workflow.
- `plugins/bvocal-repertoire/` — the Repertoire Item content type for performance
  songs, scores, lyrics, and practice recordings.
- `docs/` — local-development guidance and project notes.

## What does not belong here

The following are deliberately local-only and ignored by Git:

- WordPress core, uploads, database files, and third-party plugins.
- `docker-compose.yml`, which contains this computer's local database settings.
- API tokens, exports, backups, and other credentials.

The tracked [`docker-compose.example.yml`](docker-compose.example.yml) is a
credential-free starting point for local configuration.

## Local development

This checkout expects an existing local WordPress installation and local database
content. Copy `docker-compose.example.yml` to `docker-compose.yml`, choose
local-only database passwords, and use settings that match the local database.
Then start the site:

```sh
docker compose up -d
```

The local site normally runs at <http://localhost:8091>.

For the full setup and content-handling rules, see
[Local development](docs/local-development.md).

## Content and publishing boundary

The live BVOCAL site is the authoritative source of site content. Content may
be refreshed **from live to local**, but local content is never committed and
must not overwrite the live site without an explicit migration decision.

Likewise, changes made here are local code changes. Publishing them to the live
site requires separate, explicit approval.

## Project notes

- [Local development](docs/local-development.md) — setup and content rules.
- [Technical roadmap](docs/bvocal-roadmap.md) — deployment lanes, theme
  migration strategy, and development priorities.
- [Site TODO](docs/bvocal-todo.md) — current follow-up work.
- [Gig RSVP plugin](plugins/bvocal-gig-rsvp/README.md) — RSVP workflow and
  MailerLite behavior.
