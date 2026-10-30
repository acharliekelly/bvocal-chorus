# BVOCAL Volunteer Guide Design

## Intent

Give BVOCAL Editors clear, current instructions for recurring website tasks
without making those instructions public or requiring volunteers to maintain
them in WordPress.

## Decision

The versioned Markdown files bundled with BVOCAL's custom theme and plugins are
the source of truth. A small custom `bvocal-volunteer-guide` plugin discovers
those files and generates read-only, private WordPress guide articles for an
admin-dashboard guide.

This is an intentional exception to the normal Live → Local content rule:
generated guide articles flow from the version of custom code installed on a
site into that same site's database. They are not editorial site content and
are never migrated from local to live or edited in WordPress.

## Audience and permissions

- Editors and Administrators can open and read **Volunteer Guide** in the
  WordPress dashboard. The menu and article views require `edit_others_pages`.
- Only Administrators can run a sync, view source/sync status, or otherwise
  manage generated guides. Those actions require `manage_options` and a nonce.
- Generated articles have no normal WordPress editing screen. WordPress is a
  rendered destination, not an authoring surface.

## Source documents

Initially, the synchronizer discovers only these versioned paths:

- `themes/bvocal/KB.md`
- `plugins/bvocal-*/KB.md`

Each document begins with one level-one Markdown heading, used as the guide
title. The rest is task-oriented Markdown. Empty, malformed, unreadable, or
duplicate source identities do not overwrite a previously generated article;
they are reported to Administrators.

The first documentation set will cover the theme/editor workflow, Gig RSVP,
and Repertoire. New custom BVOCAL components add their own `KB.md` beside the
code they describe.

## Synchronization behavior

The plugin records each guide's source identity and content hash. On an
Administrator visit to the Volunteer Guide after a code release, it compares
the installed Markdown files with those hashes and synchronizes only new or
changed documents. An Administrator-only **Resync guide** control repeats the
same process on demand.

If a source document disappears, its generated article is hidden from Editors
but retained as an inactive record for recovery; it is never automatically
deleted. Unchanged articles are not rewritten.

## Display

The top-level **Volunteer Guide** dashboard page lists current guide titles,
their source component, and a short excerpt. Selecting a guide opens a
read-only dashboard article view. Markdown is rendered to sanitized HTML; raw
source HTML and script content are not trusted.

No public URLs, front-end templates, search, article editing, or generic
knowledge-base features are part of the first release.

## Documentation workflow

When a custom-code change changes a volunteer task:

1. Update that component's `KB.md` in the same feature branch as the code.
2. Verify the guide locally through the Volunteer Guide dashboard.
3. Review and approve the code release normally.
4. Deploy the code and let an Administrator visit the live Volunteer Guide, or
   use **Resync guide**, to create the matching live guide article.

This keeps the documentation paired with the exact feature version it explains.

## Non-goals

- Replacing Google Drive as the home for unrelated working documents.
- Importing the general-purpose BKJ knowledge-base UI or its broader sync and
  orphan-management behavior.
- Letting Editors alter generated instructions inside WordPress.
- Automatically deleting old generated guide articles.
