# Gig RSVP Publication Safety Design

## Intent

Publishing a future Gig must never silently leave public visitors without its
expected RSVP path. Editing a draft must not create any MailerLite resources.

## Goals

- Let an editor select **Enable RSVP for this Gig** while creating or editing
  an Event, without leaving the Event editor or contacting MailerLite.
- Create the per-Gig MailerLite group only after WordPress has successfully
  published a future Event.
- Keep publishing successful even if MailerLite is unavailable.
- Make an RSVP failure prominent in the Gig RSVP dashboard, with copy that
  directs the administrator to create a Google Form and paste its link into
  the Event description as the temporary fallback.
- Offer dashboard enable/retry controls only for published future Events.

## Non-goals

- Do not create groups for drafts, private Events, or Events already past.
- Do not block publication while waiting for MailerLite.
- Do not automate Google Form creation or change the public Event description.

## Approach

The Event editor stores a boolean RSVP-requested post meta value through a
normal checkbox in its existing publish form. A WordPress post-status hook
detects the transition to `publish`; when the Event is future-dated and RSVP
is requested, it creates the group. API failure is stored as event-specific
error state instead of rolling back publication.

The dashboard separates published future Events into ready and attention
states. Attention rows show an unmistakable warning, the saved MailerLite
error, a retry action, and exact Google Forms fallback instructions. Its
enable/retry action creates a group only after a deliberate admin click.

## Constraints

- Reuse the existing `mailerlite_api_key` WordPress option; never duplicate or
  display the token.
- Preserve RSVP response records when a MailerLite group is deleted.
- Keep the feature in the existing custom `bvocal-gig-rsvp` plugin.
- All state-changing administrator controls require WordPress capability and
  nonce checks.
