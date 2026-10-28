# BVOCAL local development

This repository contains custom BVOCAL code only:

- `themes/bvocal/` is the custom theme.
- `plugins/` is reserved for future custom plugins.
- `wordpress/` and `docker/db-data/` are local-only WordPress and database data.

Start the local site with `docker compose up -d`. Docker mounts the tracked
theme into the ignored local WordPress installation.

## Content rule

Live is the authoritative BVOCAL content source. Refresh content only from
live to local. Do not commit database exports, uploads, WordPress core, or
third-party plugins, and do not use local content to overwrite live without
an explicit, separate migration decision.
