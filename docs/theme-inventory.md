# BVOCAL theme and content-dependency inventory

Use this document to map one representative example of each BVOCAL page
archetype before changing its theme treatment. It is not a content migration
plan and should not be used to copy content from local or staging back to live.

## How to use this inventory

For each row:

1. Open the public page on live BVOCAL and the corresponding local or staging
   page when available.
2. Record which WordPress screen, plugin, block, menu, or setting supplies the
   content.
3. Separate the live-owned inputs from the theme-owned presentation.
4. Describe the smallest useful visual or structural change.
5. Choose the expected deployment lane:
   - **Code only** — custom theme/plugin code works with existing content.
   - **Code + live configuration** — code also needs a documented setting,
     menu assignment, plugin setting, or service connection on live.
   - **Feature-specific migration** — code needs precise new or changed live
     records, documented separately before deployment.

Do not use “whole-site push,” database import, media synchronization, or
direct SQL as a deployment lane.

## Inventory

| Archetype | Representative live URL | Template or current layout | Live-owned inputs and source | Plugin/integration dependencies | Smallest desired change | Deployment lane | Notes / verification status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Homepage | `https://bvocalchorus.org/` | `front-page.html` | Front-page blocks, hero-media attachment, CTA destinations. Local (`http://localhost:8091/`) has the intended replacement content; live's older homepage content is being retired. |  | Restore the intended hero and use it with the new homepage treatment. | Feature-specific migration | Hero image is absent from staging's Media Library. Do not solve this by copying a database or uploads directory. Identify the local source file, upload it deliberately to staging, verify the relevant homepage block points to it, then create a separate approved live content-migration checklist. |
| Standard page |  | `page.html` | Title, blocks, media |  |  |  | Not started |
| Blog archive |  | `archive.html` | Posts, categories, featured images |  |  |  | Not started |
| Single post |  | `single.html` | Post content, featured image, author/date metadata |  |  |  | Not started |
| Events archive | `/events/` | The Events Calendar output | Event records and event categories | The Events Calendar |  |  | Not started |
| Single Gig |  | Event template plus Gig RSVP output | Event date, venue, organizer, category, RSVP state | The Events Calendar, Gig RSVP, MailerLite |  |  | Not started |
| Repertoire archive | `/repertoire/` when deployed | `archive-repertoire_item.html` | Repertoire Item records | BVOCAL Repertoire, Google Drive |  |  | Not started |
| Repertoire item |  | `single-repertoire_item.html` | Credits, lyrics, score, recordings | BVOCAL Repertoire, Google Drive |  |  | Not started |
| Existing Song |  | Legacy song layout | Existing Song records and fields |  |  |  | Not started |
| Past Event |  | Current theme/plugin layout | Event narrative, media, linked resources | YouTube, Facebook, Instagram as applicable |  |  | Not started |
| Contact / form page | `/contact-us/` | `page.html` plus form output | Page blocks and form configuration | WPForms Lite |  |  | Not started |
| Member-only page |  | `page.html` or plugin layout | Protected page content and access settings | Member-content tooling, if applicable |  |  | Not started |

## First staging milestone

The first stakeholder-ready staging preview should cover a coherent set of
representative page types rather than every route:

- homepage;
- one standard page;
- events archive;
- one Single Gig; and
- mobile navigation and layout.

Complete the corresponding rows before selecting that milestone's theme work.
