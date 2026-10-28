# BVOCAL Homepage CTA Rail Design

## Intent

Make the homepage hero feel like one continuous composition instead of being
surrounded by empty white space, while preserving the visual treatment of every
other page.

## Goals

- Apply black to the homepage-only desktop CTA rail and the empty area below the
  hero.
- Make the fixed CTA buttons larger and easier to use.
- Add a WordPress search form above the CTA actions.
- Add icon-style YouTube, Facebook, and Instagram links below the actions.
- Let an administrator set each social URL in BVOCAL Theme Settings; hide a
  platform whose URL is empty.

## Non-goals

- Do not use Elementor, add an icon package, alter existing menus, or change
  other templates.
- Do not change site content or the local site URL configuration.

## Approaches

- **Selected:** CSS scopes the rail/background treatment to WordPress's existing
  `home` body class; the template contributes the search and social markup.
  A small native WordPress settings page stores the three URLs.
- **Rejected:** A custom hero block would create a second editing model for a
  homepage that the existing block template already represents.

## Decisions

- Use inline SVG social icons with visually hidden text for accessible names.
- Keep the existing mobile CTA rail inline; the visual expansion is desktop-led.
- Store URLs in the `bvocal_theme_options` option and sanitize them as URLs.

