# BVOCAL Site TODO

## BVOCAL Theme

- fix page patterns for softer, smoother look

### Mobile and presentation review — 2026-11-05

Mobile is a primary audience: visitors arrive through QR codes at actions.
Complete planned changes on staging before the stakeholder demo and coordinated
live release. These tasks are not yet implemented.

- [ ] Keep primary and footer menus on one line on most phones, retaining
  readable text, usable tap targets and a fallback for narrower screens.
- [ ] Improve mobile hero composition: avoid the white-on-red “POWERING THE
  MOVEMENT” label clashing with a different red area in the background photo.
- [ ] Reduce mobile header and sidebar/action-area height (reported around 60%
  of the viewport): less logo padding and search field/button on one row.
  Verify both logged-out visitor and admin-toolbar views.
- [ ] Show persistent active states on all menus, based on the hover treatment;
  distinguish current page and containing section, and retain keyboard focus.
- [ ] Standardize image sizes and text alignment on `/song-sheets-by-topic/`
  for desktop and mobile.
- [ ] Reduce featured-image dominance on individual topical song-sheet posts.
- [ ] Reduce featured-image dominance on individual Team pages so description
  and Join action are prominent; preserve the satisfactory Teams index.
- [ ] Audit page parents, navigation and breadcrumbs; rehearse repeatable
  structural changes on local, staging and live. Examples: Teams → Admin Team,
  topical song-sheet index → individual sheets, Songs → Repertoire. Decide URL
  changes separately from breadcrumb hierarchy. Use stable slugs/paths to
  resolve destination IDs, preview changes, check expected starting values,
  preserve unrelated content and record rollback. For changed URLs, document
  old/new routes, redirects and internal-link checks. No staging DB import.



## BVOCAL PLugins

### Volunteer Guide (KB-Sync)

- Add Search bar


### Gig RSVP

- More Actions:
  - Send Update (upcoming gig with ML group): send a short update message to ML Group, using a regular rich text editor; bypasses need to create a campaign in ML
  - Make Past Event (past gig): create Past Event post, add media files


### Repertoire

- Create Repertoire index page
- Create Songs -> Repertoire Items import tool


### Past Events

- Add Search bar: find past actions by date, venue
- easy to link media, esp from YouTube, Facebook, Instagram


## Documentation / Refactoring

- add inline and PHPDoc comments
- refactor code for maintainability by human or AI developers

- add deployment scripts