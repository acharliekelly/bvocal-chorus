# BVOCAL Homepage CTA Rail Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the BVOCAL homepage hero and CTA rail visually continuous, with larger actions, search, and settings-backed social links.

**Architecture:** Keep the existing block theme template and fixed CTA rail. Add a small WordPress Settings API integration in `functions.php`, expose configured social links through a dynamic `bvocal/social-links` block, and scope the visual changes to the standard homepage body class.

**Tech Stack:** WordPress block theme templates, PHP Settings API, CSS, inline SVG.

**Spec:** `docs/superpowers/specs/2026-10-27-homepage-cta-rail-design.md`

## Global Constraints

- Homepage-only black treatment; other templates retain their current appearance.
- No dependencies or external icon library.
- Blank social URLs hide their corresponding link.
- Use inline SVG plus accessible text for social actions.

## Review Focus

- Empty social settings must render no empty anchors; verify with all three URLs blank.
- Social URLs must be escaped and output only as safe HTTP(S) links.
- Search remains usable by keyboard and has an accessible label.
- Desktop rail remains clear of homepage content; mobile keeps its inline four-action layout.
- Non-home pages must not acquire the black rail background.

---

### Task 1: Theme settings and social-link renderer

**Files:**
- Modify: `wordpress/wp-content/themes/bvocal/functions.php`
- Create: `wordpress/wp-content/themes/bvocal/blocks/social-links/block.json`
- Create: `wordpress/wp-content/themes/bvocal/blocks/social-links/render.php`

**Interfaces:**
- Produces: `bvocal_get_theme_options(): array` and the dynamic `bvocal/social-links` block for the header template.

- [ ] Register `bvocal_theme_options` with `register_setting()` and sanitize the
  `youtube_url`, `facebook_url`, and `instagram_url` fields using `esc_url_raw()`.
- [ ] Add a BVOCAL Theme Settings page under Appearance with the three labeled URL fields.
- [ ] Implement `bvocal_render_social_links()` to return icon links only for
  non-empty settings, with inline SVG and screen-reader-only platform names.
- [ ] Register `blocks/social-links` with the other BVOCAL dynamic blocks.
- [ ] Run `php -l wordpress/wp-content/themes/bvocal/functions.php`.
- [ ] In Appearance → BVOCAL Theme Settings, save a valid URL, an invalid URL,
  and blank values; confirm valid entries persist and blank entries render no link.

### Task 2: Homepage CTA rail markup and visual treatment

**Files:**
- Modify: `wordpress/wp-content/themes/bvocal/parts/header.html`
- Modify: `wordpress/wp-content/themes/bvocal/assets/theme.css`

**Interfaces:**
- Consumes: the `bvocal/social-links` dynamic block from Task 1.

- [ ] Add the native WordPress search form above the existing CTA actions.
- [ ] Add the `bvocal/social-links` block below the actions.
- [ ] Scope the black reserved rail and empty-homepage treatment to the `home` body class at desktop widths.
- [ ] Increase desktop CTA rail width, action padding, and action type size without changing the mobile grid's four buttons.
- [ ] Style icon links with visible focus states and maintain a minimum practical touch target.
- [ ] Verify on the local homepage that the hero has no white right/bottom gap,
  search reaches the existing search results template, and configured social links open their configured destinations.
- [ ] Verify a page such as `/about/` retains its white page background and that a narrow viewport retains the inline CTA grid.

### Task 3: Local visual and regression verification

**Files:**
- No source changes expected.

- [ ] Run `php -l wordpress/wp-content/themes/bvocal/functions.php`.
- [ ] Load `http://localhost:8091/`, `/about/`, and a search URL locally; confirm no PHP warning or database error is present.
- [ ] Inspect desktop and mobile-width homepage layouts for rail overlap, visible focus, and readable icon contrast.
- [ ] Commit the implementation with `feat: refresh BVOCAL homepage action rail`.
