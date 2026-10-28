# BVOCAL Gig RSVP

This custom plugin gives every The Events Calendar Gig its own RSVP form and
its own MailerLite group. It deliberately keeps the RSVP answer, ride request,
and comments in WordPress because those answers belong to one particular Gig;
MailerLite subscriber fields are shared across all of a person's groups.

## Local setup

The Compose configuration mounts this plugin into local WordPress. Activate it
from **Plugins** once, then visit **Gig RSVP** in the WordPress dashboard.

The official MailerLite plugin continues to own the `mailerlite_api_key` option.
This plugin reads that existing setting and never stores a second copy of the
token.

## Comms workflow

1. Create an Event for the upcoming Gig.
2. In its **Gig RSVP** panel, choose **Enable RSVP for this Gig**.
3. The plugin creates a clearly named MailerLite group and appends the RSVP
   form to that Gig's public event page.
4. Responses are saved once per person per Gig. A later response updates that
   person's RSVP for the same Gig.
5. After the Gig date has passed, the dashboard stops showing its public form
   and offers a manual, confirmed **Delete RSVP group** control. Deleting the
   group leaves the local RSVP record intact and does not delete subscribers.

The future “create Past Event and add media” step is intentionally out of scope
for this plugin version.

## Checks

Run the small pure-PHP check with:

```sh
php plugins/bvocal-gig-rsvp/tests/group-naming-test.php
```
