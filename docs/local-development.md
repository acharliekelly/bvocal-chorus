# BVOCAL local development

This repository contains custom BVOCAL code only:

- `themes/bvocal/` is the custom theme.
- `plugins/` is reserved for future custom plugins.
- `wordpress/`, `docker/db-data/`, and `docker-compose.yml` are local-only
  WordPress, database, and machine configuration.

Before starting a new local installation, copy `docker-compose.example.yml` to
`docker-compose.yml` and replace its placeholder database passwords with
local-only values. The real Compose file is ignored and must never be
committed.

Start the local site with `docker compose up -d`. Docker mounts the tracked
theme and custom plugins into the ignored local WordPress installation.

## Content rule

Live is the authoritative BVOCAL content source. Refresh content only from
live to local. Do not commit database exports, uploads, WordPress core, or
third-party plugins, and do not use local content to overwrite live without
an explicit, separate migration decision.
