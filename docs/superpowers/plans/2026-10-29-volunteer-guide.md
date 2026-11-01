# Volunteer Guide Implementation Plan

> **For agentic workers:** Implement this plan directly, one checked task at a time. Do not use subagents. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Provide Editor-readable, Administrator-managed volunteer workflow documentation generated from the Markdown files that ship with BVOCAL custom code.

**Architecture:** A new `bvocal-volunteer-guide` plugin discovers a tightly scoped set of `KB.md` files, converts safe Markdown to private generated guide posts, and renders those posts only in a dashboard menu. Hash metadata prevents needless rewrites. The plugin synchronizes only after an Administrator reaches the guide or explicitly requests a resync.

**Tech Stack:** WordPress 6+, PHP 8+, custom plugin, private custom post type, bundled safe Markdown parser, WordPress nonces/capabilities.

**Spec:** `docs/superpowers/specs/2026-10-29-volunteer-guide-design.md`

## Global Constraints

- Source documents are limited to `themes/bvocal/KB.md` and `plugins/bvocal-*/KB.md`.
- Editors read guides through `edit_others_pages`; only Administrators use `manage_options` sync controls.
- Generated content is private, read-only in WordPress, and never automatically deleted.
- Synchronization must not run for Editors, front-end requests, cron, or unrelated admin requests.
- Markdown output must be sanitized before storage or display.

## Review Focus

- A non-Administrator visits the guide: content is readable, but no synchronization or maintenance control runs. Test in Task 3.
- A source file is removed after a prior sync: its old guide disappears from the Editor index without deletion. Test in Task 2.
- A malformed or duplicate source appears: the prior working article remains intact and an Administrator sees the failure. Test in Task 2.
- An unchanged source is found during a later administrator visit: its post is not rewritten. Test in Task 2.
- Markdown containing script or unsafe HTML: stored/output guide content is sanitized. Test in Task 1.

---

### Task 1: Plugin scaffold and source-document parser

**Files:**
- Create: `plugins/bvocal-volunteer-guide/bvocal-volunteer-guide.php`
- Create: `plugins/bvocal-volunteer-guide/includes/class-guide-source.php`
- Create: `plugins/bvocal-volunteer-guide/includes/class-guide-markdown.php`
- Create: `plugins/bvocal-volunteer-guide/tests/source-document-test.php`
- Create: `themes/bvocal/KB.md`
- Create: `plugins/bvocal-gig-rsvp/KB.md`
- Create: `plugins/bvocal-repertoire/KB.md`

**Interfaces:**
- Produces `BVOCAL_Guide_Source::discover(): array`, returning source identity,
  title, component label, Markdown body, and SHA-256 hash.
- Produces `BVOCAL_Guide_Markdown::render( string $markdown ): string`,
  returning sanitized HTML.

- [ ] Write source-parser tests for the three approved paths, a missing file,
  duplicate identity, a title-less document, and unsafe HTML.
- [ ] Run the test and confirm it fails before the parser exists.
- [ ] Implement scoped source discovery, required level-one title extraction,
  content hashing, and safe Markdown rendering.
- [ ] Add short, task-oriented starting guides for theme/editor work, Gig RSVP,
  and Repertoire; they should describe the existing workflows only.
- [ ] Run the parser test and PHP syntax checks; confirm they pass.
- [ ] Commit the scaffold, parser, and initial source documents.

### Task 2: Generated-guide storage and synchronization

**Files:**
- Create: `plugins/bvocal-volunteer-guide/includes/class-guide-sync.php`
- Create: `plugins/bvocal-volunteer-guide/tests/sync-test.php`
- Modify: `plugins/bvocal-volunteer-guide/bvocal-volunteer-guide.php`

**Interfaces:**
- Consumes `BVOCAL_Guide_Source::discover()` and
  `BVOCAL_Guide_Markdown::render()`.
- Produces `BVOCAL_Guide_Sync::sync(): array` and registers the private
  `bvocal_guide` post type.
- Stores `_bvocal_guide_source`, `_bvocal_guide_hash`, and
  `_bvocal_guide_active` metadata on generated posts.

- [ ] Write sync tests for create, changed-source update, unchanged-source
  no-op, invalid-source preservation, and removed-source deactivation.
- [ ] Run the test and confirm it fails before synchronization exists.
- [ ] Implement a private, non-editable post type and hash-based upsert logic.
  Deactivate missing sources rather than trashing or deleting their posts.
- [ ] Run sync and syntax tests; confirm all scenarios pass.
- [ ] Commit generated-guide storage and synchronization.

### Task 3: Dashboard guide and access boundaries

**Files:**
- Create: `plugins/bvocal-volunteer-guide/includes/class-guide-admin.php`
- Create: `plugins/bvocal-volunteer-guide/tests/admin-access-test.php`
- Modify: `plugins/bvocal-volunteer-guide/bvocal-volunteer-guide.php`
- Modify: `README.md`

**Interfaces:**
- Consumes `BVOCAL_Guide_Sync::sync()` and active `bvocal_guide` posts.
- Produces an Editor-readable `Volunteer Guide` dashboard menu and an
  Administrator-only resync action.

- [ ] Write access tests covering Editor guide access, denied Editor resync,
  Administrator resync with a valid nonce, and no sync on unrelated requests.
- [ ] Run the test and confirm it fails before dashboard code exists.
- [ ] Implement the index and article views with `edit_others_pages` access.
  Make resync use `admin_post`, `manage_options`, and a nonce.
- [ ] Add guarded administrator-visit synchronization only for the Volunteer
  Guide pages; display source errors only to Administrators.
- [ ] Link the Volunteer Guide and documentation workflow from the repository
  README.
- [ ] Run all new tests, existing Gig RSVP/Repertoire tests, syntax checks, and
  a local dashboard verification as both an Editor and Administrator.
- [ ] Commit the dashboard and documentation.
