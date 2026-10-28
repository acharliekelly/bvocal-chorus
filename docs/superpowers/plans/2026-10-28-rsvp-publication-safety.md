# Gig RSVP Publication Safety Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make per-Gig RSVP provisioning safe for Event publication and visible when MailerLite fails.

**Architecture:** Store an RSVP-requested flag in the Events Calendar editor, then create the MailerLite group only after a future Event reaches published status. Store failure state on the Event and show it prominently in the existing Gig RSVP dashboard, where an administrator can retry or follow Google Forms fallback instructions.

**Tech Stack:** WordPress PHP, The Events Calendar, WordPress post meta and transition hooks, MailerLite REST API.

**Spec:** `docs/superpowers/specs/2026-10-28-rsvp-publication-safety-design.md`

## Global Constraints

- Never create a MailerLite group for a draft, private, or past Event.
- Publication must succeed when MailerLite fails.
- Reuse `mailerlite_api_key`; never display or duplicate the token.
- All state-changing administrator controls require capability and nonce checks.

## Review Focus

- Publishing an RSVP-requested Event while MailerLite fails leaves the Event published and records an actionable error.
- Saving an RSVP-requested draft does not call MailerLite or create a group.
- Dashboard controls are absent for drafts and past Events.
- A successful retry clears the prior error and enables the public RSVP form.
- A published future Event with RSVP requested but no group is visually distinguished from an Event that never requested RSVP.

---

### Task 1: Store RSVP intent in the Event editor

**Files:**
- Modify: `plugins/bvocal-gig-rsvp/bvocal-gig-rsvp.php`
- Test: `plugins/bvocal-gig-rsvp/tests/editor-form-safety-test.php`

**Interfaces:**
- Produces: `META_REQUESTED` event meta, saved with the Event's normal editor form.

- [ ] **Step 1: Write the failing test**

Assert the Event-editor RSVP markup contains a checkbox named for the RSVP-requested meta and no nested form.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php plugins/bvocal-gig-rsvp/tests/editor-form-safety-test.php`

Expected: FAIL because the checkbox does not exist.

- [ ] **Step 3: Add the checkbox and save handler**

Render the checkbox in the Event meta box and save the boolean only during a normal `tribe_events` post save. Do not call MailerLite from this handler.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php plugins/bvocal-gig-rsvp/tests/editor-form-safety-test.php`

Expected: PASS.

### Task 2: Provision after publication and record failures

**Files:**
- Modify: `plugins/bvocal-gig-rsvp/bvocal-gig-rsvp.php`
- Create: `plugins/bvocal-gig-rsvp/tests/publication-safety-test.php`

**Interfaces:**
- Consumes: `META_REQUESTED` from Task 1.
- Produces: stored RSVP group metadata or `META_ERROR` text for dashboard display.

- [ ] **Step 1: Write failing tests**

Cover future publish calling the provisioning method, drafts/past Events not calling it, and a MailerLite error leaving the Event published while recording `META_ERROR`.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php plugins/bvocal-gig-rsvp/tests/publication-safety-test.php`

Expected: FAIL because publication provisioning and error state do not exist.

- [ ] **Step 3: Add the publication hook and provisioning helper**

On transition to `publish`, provision only requested future Events that have no group. Catch API failures, store a short error message, and never interrupt WordPress publication.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php plugins/bvocal-gig-rsvp/tests/publication-safety-test.php`

Expected: PASS.

### Task 3: Limit dashboard actions and add fallback guidance

**Files:**
- Modify: `plugins/bvocal-gig-rsvp/bvocal-gig-rsvp.php`
- Modify: `plugins/bvocal-gig-rsvp/README.md`
- Test: `plugins/bvocal-gig-rsvp/tests/publication-safety-test.php`

**Interfaces:**
- Consumes: group metadata, `META_REQUESTED`, and `META_ERROR`.
- Produces: ready, retry-required, or unavailable dashboard rows.

- [ ] **Step 1: Write failing tests**

Assert that only published future Events render enable/retry actions and an error row includes Google Forms fallback instructions.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php plugins/bvocal-gig-rsvp/tests/publication-safety-test.php`

Expected: FAIL because current rows offer controls to drafts and do not show the fallback.

- [ ] **Step 3: Implement the dashboard states**

Show a high-visibility warning and retry link for a published future requested Event with an error. Explain the Google Form fallback: create a per-Gig form and paste its link in the Event description. Hide enable/retry actions for unpublished and past Events.

- [ ] **Step 4: Run local verification and update the README**

Run: `php plugins/bvocal-gig-rsvp/tests/editor-form-safety-test.php && php plugins/bvocal-gig-rsvp/tests/publication-safety-test.php && php -l plugins/bvocal-gig-rsvp/bvocal-gig-rsvp.php`

Expected: all checks pass with no syntax errors.

- [ ] **Step 5: Commit**

```bash
git add plugins/bvocal-gig-rsvp docs/superpowers/specs/2026-10-28-rsvp-publication-safety-design.md docs/superpowers/plans/2026-10-28-rsvp-publication-safety.md
git commit -m "Make RSVP provisioning publication-safe"
```
