# BVOCAL technical roadmap

This roadmap guides custom-code work on BVOCAL without treating a local or
staging WordPress copy as a replacement for the live site.

It is a developer guide for the project maintainers. The detailed feature
backlog remains in [Site TODO](bvocal-todo.md).

## Operating boundaries

| Source | Owns | Must not become the source for |
| --- | --- | --- |
| Git repository | Custom theme, custom plugins, tests, deployment scripts, and documentation | WordPress content, uploads, third-party plugins, credentials, or database exports |
| Live BVOCAL | Published content, media, events, menus, settings, user-managed plugin configuration, and service connections | Unreviewed local code |
| Local Docker | Code development and fast functional testing against a local content snapshot | Live content changes or deployment decisions |
| InstaWP staging | Hosted verification, reviewer previews, and deployment rehearsal | A whole-site push back to live |

Live content remains authoritative. Refreshing a local content snapshot from
live is allowed. Moving a local or staging database, uploads directory, or
whole site back to live is not part of the normal workflow.

## Deployment lanes

Choose one lane before starting a change. If a change needs more than one
lane, record each one in the feature's release note or pull request.

### 1. Code only

Use for PHP, templates, CSS, JavaScript, and documentation that work with
existing live content.

1. Develop and verify locally.
2. Create the staging ZIPs with `npm run verify:staging` and
   `npm run package:staging`.
3. Upload the custom theme or plugin ZIP to staging and verify the relevant
   user workflow.
4. Obtain explicit approval before installing the same code on live.
5. Verify the live workflow after deployment.

### 2. Code plus live configuration

Use when the code needs a WordPress setting, menu assignment, plugin setting,
service connection, or other configuration that belongs to the live site.

Deploy and verify the code first. Then reproduce the documented configuration
through the live WordPress admin. Record the exact setting and its expected
effect in the relevant plugin `KB.md`, README, or release note.

Examples include a MailerLite token, a Google Drive connection, a menu item,
or an Event category term.

### 3. Feature-specific content migration

Use only when a feature cannot work without new or changed live content.

Before making the change, create a small migration record containing:

- the purpose and the exact live records affected, identified by stable slugs
  or IDs;
- any new post types, terms, pages, menus, media, or fields required;
- the approved method: manual WordPress-admin steps, a narrowly scoped
  WordPress CLI command, or a documented import tool;
- a rollback path; and
- before-and-after verification steps.

Do not use a full database import, a whole-site staging push, bulk uploads
synchronization, or direct SQL as a shortcut. Those approaches can overwrite
new articles, events, RSVP data, media, and volunteer changes made on live.

## Theme migration strategy

The theme should evolve around live-owned content rather than attempt to
recreate the site from a local snapshot.

1. **Map before replacing.** For each page pattern or template, record which
   live pages, blocks, menus, media, and plugin shortcodes it renders.
2. **Separate presentation from content.** Put reusable layout and styling in
   the theme. Keep page copy, event records, song data, media, and navigation
   assignments in WordPress.
3. **Prefer backwards-compatible templates.** A new template should render
   existing content acceptably before it introduces optional new fields or
   blocks.
4. **Add content deliberately.** If a design needs a new page, block pattern,
   menu location, or field value, document the live-admin step or make a
   feature-specific migration record.
5. **Use staging as a rehearsal.** Verify both existing copied content and a
   representative new item. Do not assume a local-only post proves the live
   migration path.

## Roadmap phases

### Phase 0 — Reliable delivery foundation

- Maintain the local Docker environment and the no-indexed InstaWP staging
  site used for developer testing and stakeholder previews.
- Keep outbound WordPress email disabled and MailerLite disconnected on
  staging unless a specific, controlled integration test requires otherwise.
- Use the staging package commands for custom code.
- Document each deployment's manual live configuration separately from code.

### Phase 1 — Theme and content-dependency inventory

- Inventory the active templates, reusable blocks, page patterns, menus, and
  third-party shortcodes used by live BVOCAL pages.
- Maintain the [theme and content-dependency inventory](theme-inventory.md)
  with representative live pages and their expected deployment lanes.
- Identify theme changes that are code-only versus those requiring new live
  content or configuration.
- Establish a short release checklist for theme changes: representative pages,
  mobile layout, forms, calendar, and navigation.

### Phase 2 — Theme refinement

- Improve page patterns toward the softer, smoother visual direction in the
  TODO.
- Make focused, backwards-compatible template and CSS changes.
- For each new content requirement, add a live-admin instruction or a
  feature-specific migration record before deployment.

### Phase 3 — Custom workflow improvements

- Volunteer Guide: add a guide search interface.
- Gig RSVP: add controlled update-email and Past Event handoff actions.
- Repertoire: add an index page and a Songs-to-Repertoire import path.
- Past Events: add search and simpler media linking from the supported video
  and social sources.

Each item is its own feature branch and release decision. Do not combine an
unrelated theme migration with a workflow change merely because both are ready
at the same time.

### Phase 4 — Maintainability

- Add concise PHPDoc and inline comments where custom behavior is not obvious.
- Refactor only when a real maintenance need is identified and covered by
  verification.
- Keep each custom plugin's `KB.md` current so the Volunteer Guide reflects
  the working site.

## Definition of done for a release

Before a custom-code change reaches live, confirm:

- the relevant local checks pass;
- the exact ZIPs are verified on staging;
- staging did not send email or contact MailerLite unexpectedly;
- any live configuration or content migration is written down and approved;
- the live deployment has explicit approval; and
- the deployed live workflow has been manually verified.
