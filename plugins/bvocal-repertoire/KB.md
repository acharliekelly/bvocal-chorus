# Add a Repertoire Item

Add the title and author, then choose a score PDF and practice recordings from
the Media Library. Save the item as a draft until the information is complete.

Under **Links**, click **Add link** for each performance or other resource.
Enter a descriptive label and complete http:// or https:// URL. Use **Remove**
to delete a row, then save. Links appear as a list on the item and open in new
tabs; no players are embedded. Leave the list empty to omit the section.
If a URL is invalid, the previous Links list is retained and an editor notice
explains that the list was not saved.

# Preview the legacy Songs conversion

Version 0.1.1 adds a read-only WP-CLI command. It does not create drafts,
update Songs, download media, or publish anything. It has no apply mode.

From a WordPress installation with this plugin active:

```sh
wp bvocal repertoire preview-songs --format=table
wp bvocal repertoire preview-songs --format=json > songs-preview.json
wp bvocal repertoire preview-songs --song=1789
```

The ID in the last example is local Hold On; look up the destination site's
Song ID rather than assuming IDs match. Only published Songs are included;
private/test records are excluded even when requested by ID. Full JSON is the
default. The table is only a summary; inspect JSON before deciding anything.

For the local Docker checkout, prefix these commands with:

```sh
docker compose run --rm --user root -e WORDPRESS_TABLE_PREFIX=3Ge_ wpcli wp --allow-root
```

Each report includes source identity and a content fingerprint, proposed draft
fields, resolved media references, warnings, and unmapped source blocks. Explicit
credits map conservatively; ambiguous attribution stays in notes. Recording
labels are preserved when present. Missing labels and author credits need review.
Lyrics retain line/stanza breaks; formatting becomes plain text and is flagged.
Existing older custom fields are reported for comparison, not used to overwrite
current block content.

Drive-backed score/audio blocks resolve through that site's Media Library,
including legacy stream links. A missing or ambiguous catalogue record is an
exception; no file is uploaded or copied. Media validation checks catalogue/type
compatibility, not remote availability or playback.

As of version 0.1.2, performance links and uploaded videos map into the proposed
Links list. Their descriptions or captions become labels, and their URLs stay
on the source site or external service. Invalid links remain unmapped with their
source blocks for review. Other unsupported blocks and suspected placeholder
text are also retained for review.
No featured images are assigned by the preview.

Run the same command against staging's own content after deploying this plugin,
and eventually live's own content after approval. Do not import this report as
staging content into live. Use the separate draft-creation command below only
when ready. Configure the default featured image as described below before
creating drafts. Publication and redirects remain separate work.

## Verify preview changes

```sh
wp eval-file wp-content/plugins/bvocal-repertoire/tests/song-preview-test.php
wp eval-file wp-content/plugins/bvocal-repertoire/tests/links-test.php
```

Inspect one simple Song, Hold On, a Song with uploaded performance video, and a
Song with missing author credit. Compare JSON with the original Song editor.
An unknown Song ID, a private Song ID, or `--apply` must fail without writes.

## Verify Links in the editor

Create a local draft with an author. Add two Links, add and remove a third row,
then Save Draft. Reload and confirm that the two saved rows remain. Preview
the item and confirm that both are ordinary new-tab links with no embedded
player. Remove the temporary draft when finished. Deploy this plugin ZIP to
staging for rehearsal; live code deployment still requires separate approval.
Existing items need no migration to support an empty Links section.

# Create Repertoire drafts from Songs

Version 0.1.3 adds a separate conversion command. It defaults to a read-only
plan; **--apply** is required to create drafts. The original preview command
remains read-only and still rejects --apply.

```sh
wp bvocal repertoire convert-songs --format=table
wp bvocal repertoire convert-songs --song=1789
wp bvocal repertoire convert-songs --apply --user=<administrator> > conversion-run.json
```

Replace `<administrator>` with an actual administrator login or ID. For local
Docker, use the prefix shown above. IDs and administrator accounts belong to
the destination site; never assume staging IDs match live.

The plan reports would_create, blocked_conflict, blocked_unmapped, or blocked
source/visibility decisions. Apply checks again after each new draft: overlapping
material between source Songs can therefore cause additional items to be held
back as the run proceeds. Creation follows these rules:

- Create only new drafts from published, unprotected Songs. Private/test items
  and password-protected content are excluded.
- Keep original Songs, URLs, uploads, and existing Repertoire items unchanged.
- Hold back sources containing unmapped components rather than losing them.
- Flag existing items with matching title/slug, identical lyrics, or shared
  score/recording identities for manual review. A possible match is not a merge.
- Store mapped fields, Links, review warnings, source Song ID, source fingerprint,
  and conversion run ID on each new draft.
- Permit incomplete credits and labels only as drafts requiring human review.
  This does not establish that any draft is publication-ready.
- On rerun, skip existing conversions even if someone has edited them. Report
  changed source data without overwriting the draft. A conversion in Trash is
  also skipped; restore/review it instead of creating a duplicate.

The fingerprint covers source content/title, mapped fields, media identities,
and relevant legacy fields. Mapping-code changes may also cause a conservative
source-changed report. No refresh/overwrite operation is provided.

Only one apply command can run at a time. A normal exit releases its lock. If a
process is killed, inspect `wp option get bvocal_repertoire_conversion_lock`
and verify no conversion is running before manually removing that stale lock.

Keep the JSON run report: it records created IDs and decisions. To roll back,
review the IDs with status `created` and move only those drafts to Trash using
WordPress admin. Do not delete original Songs or pre-existing Repertoire items.
Once drafts have been edited/published, review them individually before rollback.

Rehearse locally and then independently on staging's own content. Run the same
code against live's own Songs only after approval. Do not import converted
staging records into live. Publishing, index-link cutover, and redirects remain
separate approved steps.

## Verify draft conversion

```sh
wp eval-file wp-content/plugins/bvocal-repertoire/tests/song-conversion-test.php
```

The test creates and cleans up temporary local fixtures. It checks draft status,
source preservation, structured fields, reruns, manual edits, source changes,
Trash, manual duplicates, permissions, and visibility. Review actual converted
drafts in WordPress and compare their lyrics, score/audio links, captions, and
credits against the original Songs before publishing anything.

## Approved These Hands notes exception

Version 0.1.4 maps the exact paragraph “Add a File block below and upload the
score PDF.” on the Song with slug `these-hands` into Source or notes, preserving
the text alongside existing notes. This is an explicitly approved exception;
other placeholder text and unsupported content remain unmapped. Run the same
preview/conversion commands against each site's own Song. Existing converted
items are still never overwritten by a rerun.

## Approved shared lyrics: Hostile Government and First Amendment

Version 0.1.5 treats source Songs `hostile-government-take-over` and
`first-amendment-2` as distinct even when their lyrics match. This is a
user-confirmed exception. It applies only to the lyrics conflict reason and
works in either conversion order, using the existing item's tracked source Song
slug rather than destination numeric IDs. Matching title/slug or shared media
still blocks conversion; unrelated identical lyrics still require review.
Hostile Government retains its own title, credits, and recording and is created
as a draft for review, without modifying First Amendment or either original Song.

# Default featured image

Version 0.1.6 enables the standard Featured Image panel for Repertoire.
Administrators can open **Repertoire → Settings**, choose an image from that
site's Media Library, and Save Changes. The approved BVOCAL default is the
musical-score illustration (`bvocal-musical-score.png`).

When an item is saved without a featured image, the plugin assigns the selected
default. This also applies to newly converted drafts. An explicitly chosen image
always takes precedence. Auto-drafts, Trash, revisions/autosaves, and other post
types are unaffected. Removing an item's featured image restores the default
when saved. Clear the setting to disable future assignment; images already
assigned remain in place. Changing the default does not replace existing images.
A deleted or trashed default image is not assigned to new items.

## Repeat on staging/live

1. Deploy the approved plugin ZIP.
2. Upload the approved musical-score asset if it is not already in that site's
   Media Library. This is one selected asset, not an uploads-directory import.
3. Open Repertoire → Settings, choose that site's copy, and Save Changes.
   Attachment IDs differ between sites; do not copy the local numeric ID.
4. Prefer doing this before conversion so new drafts receive their images.
5. For existing items, saving each item fills a missing image. A maintainer can
   instead perform a targeted backfill with the following WP-CLI command after
   previewing which existing items lack images and confirming the default:

```sh
wp eval '$ids = get_posts(["post_type"=>"repertoire_item", "post_status"=>["publish","draft","pending","private","future"], "posts_per_page"=>-1, "fields"=>"ids"]); foreach ($ids as $id) { if (!get_post_thumbnail_id($id)) { BVOCAL_Repertoire::apply_default_featured_image($id); } }'
```

The backfill touches only missing thumbnail metadata; it does not edit the item
content or replace custom images. Keep a record of changed item IDs and previous
thumbnail values for rollback. Do not remove images subsequently chosen by an
editor. This setting does not add images to archive/single templates; their
placement is a separate theme change.

## Verify default images

```sh
wp eval-file wp-content/plugins/bvocal-repertoire/tests/default-image-test.php
```

The test restores the previous setting and removes its temporary posts. In the
editor, save a new draft without selecting an image and confirm that the default
appears. Choose a different image and save again; that choice must remain. Open
an existing item to confirm that its Featured Image panel is available.

# Replay reviewed editorial corrections

Version 0.1.7 adds a bounded correction command. The converter still extracts
that site's own Songs; this follow-up applies only the audited field differences
to paired drafts. It does not create items, publish, redirect, import a database,
or replace original Songs. The separate `final-corrections.json` audit manifest
is release input, not content bundled into the plugin repository.

```sh
wp bvocal repertoire replay-corrections /private/final-corrections.json --format=table
wp bvocal repertoire replay-corrections /private/final-corrections.json --song=hold-on
wp bvocal repertoire replay-corrections /private/final-corrections.json --apply --user=<administrator> --backup=/private/new-replay-rollback.json
```

Use actual readable manifest and writable backup paths on the destination server.
For Docker, mount the manifest read-only and a private backup directory separately;
paths inside the container differ from host paths. A local root-run container owns
its private backup, so read/copy it through that same container account. Do not
loosen backup permissions merely to inspect it.

## Preflight and matching

- Preview is the default and writes no WordPress records or backup file.
- Match the source by its Song slug and the target by conversion provenance.
- Compare the current source extraction with the audit baseline. Hold unexpected
  source changes or unmapped components for review.
- Each current target field must equal either its baseline or reviewed value.
  Unexpected manual edits, extra body/excerpt content, protection, ambiguous
  targets, invalid media, or occupied URL slugs are held for review.
- Equivalent Drive view/share URLs, site-relative uploaded media, line endings,
  trailing spaces, and the approved Gallitan → Gallatin spelling rule are handled
  deliberately. Numeric attachment IDs and localhost URLs are not replayed.
- Already-correct entries, including published entries, are reported without
  edits. The two explicitly identified local manual entries may be recognized
  only if their complete fields already match; they are never edited or adopted.
- Only draft targets can be changed. Corrections never publish or unpublish.
- One blocked entry prevents the entire selected apply run from starting.
  Use `--song=<source-slug>` only after separately reviewing a narrower selection.

## Apply and recovery

Apply requires administrator context and a new absolute backup path outside the
WordPress root. A shared conversion/replay lock prevents overlapping CLI writes.
The rollback JSON is exclusively created with private permissions **before** any
correction. It records site, manifest checksum, destination IDs, original field
values and metadata presence, and planned changes. Existing files are never
overwritten. A no-change run creates no backup file.

Each target and source is checked again immediately before writing. Entries that
already match are skipped, making replay safe to repeat. An error stops further
edits and releases the lock; a runtime failure after writes begin may leave a
partial run. Inspect the JSON report and rollback file before retrying. A killed
process can leave a lock; confirm no run is active before removing it.

There is no automatic rollback command. To recover, use the backup from the same
site and restore only the affected fields, checking for subsequent editor changes
first. Restore a formerly absent metadata field by removing it, rather than
inventing an empty value. Do not restore original Songs or unrelated fields.
Keep the backup private and retain it through staging/live verification.

## Verification

```sh
wp eval-file wp-content/plugins/bvocal-repertoire/tests/correction-replay-test.php
```

The test creates and removes temporary local records. It exercises draft updates,
source/target drift, reruns, title/slug changes, structured recording labels,
media validation, schema restrictions and permission/publication guards. Before
live approval, rehearse the same manifest and command against staging's own
converted drafts and verify its report, credits, lyrics and media manually.
