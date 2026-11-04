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
staging content into live. Draft creation, rerun/provenance handling,
featured-image support, publication and redirects remain separate work.

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
