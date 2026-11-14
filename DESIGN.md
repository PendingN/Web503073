---
name: Vũ Điệu Rừng Xanh — Indoor Living
description: A warm editorial guide to choosing indoor plants for a real room.
colors:
  paper: "#f5f2ea"
  ink: "#292d28"
  muted: "#626b60"
  primary: "#384936"
  sage: "#edf0e8"
  care-ground: "#ece8df"
  terracotta: "#995239"
  line: "#dedfd6"
typography:
  display:
    fontFamily: "Lora, Georgia, serif"
    fontSize: "clamp(40px, 4.2vw, 68px)"
    fontWeight: 400
    lineHeight: 1.2
    letterSpacing: "-0.035em"
  body:
    fontFamily: "Be Vietnam Pro, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.8
rounded:
  control: "4px"
  disclosure: "4px"
spacing:
  small: "12px"
  medium: "24px"
  section: "100px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "#ffffff"
    rounded: "{rounded.control}"
    padding: "16px 22px"
---

# Landing Design

## Overview

Scope: `index.php` and its landing-only stylesheet, with the Blog extension described below. The approved landing pairs a minimal, bright room with warm editorial typography and practical plant guidance. Visitors choose plants suitable for their own light and available space. Other internal and admin pages retain their current design.

## Colors

Warm ivory is the page ground, charcoal carries headings, and muted olive carries actions. A warm stone ground separates care guidance; restrained terracotta numbers mark its steps. The finder stays on the page ground between thin rules. Plant foliage supplies the main photographic color. Use the `--room-*` tokens in `css/landing.css`; care-ground and terracotta reflect the existing section and counter declarations.

## Typography

Self-hosted Lora regular carries headings. Existing Be Vietnam Pro carries navigation, body copy and controls. The Lora license is included in `fonts/lora-OFL.txt`. Preserve Vietnamese diacritics. Headings use practical language about choosing plants, care and questions; avoid decorative italic fragments. Body guidance uses 16px text at 1.8 line-height on desktop, with smaller labels and supporting captions.

## Layout

The desktop hero separates left-aligned copy and the room photograph into 36% / 64% columns; text never overlays the photograph. At 701–1050px the split becomes 40% / 60%. Three numbered native disclosures identify plants in the scene. Content uses a 90% container capped at 1296px. The finder filters four editorial starting points by light and placement; links lead to the existing indoor catalog search. Care steps and FAQ follow the finder. A compact text link with the live catalog count closes the page instead of a second hero or large promotional block.

At 700px and below, copy precedes the room image. Plant notes expand below the image. The finder stacks and plants use two columns. Larger layouts retain four columns. Body copy remains readable; native selects use 16px on mobile to avoid input zoom.

## Elevation & Depth

Keep ordinary sections flat. Use soft shadows only to lift scene markers and desktop plant notes. The room photo supplies natural depth. The hero is static: no pointer parallax, marker pulse or entrance animation. Interaction feedback is limited to local hover and focus states.

## Shapes

Rectangular photographs, restrained 4px control corners, circular 44px scene markers and 4px desktop note corners. Avoid enclosing every section in a card.

## Components

- Native `details` provide plant and FAQ disclosures. JavaScript adds exclusive selection, Escape, and outside dismissal for the room.
- A form filters editorial suggestions locally and announces the result count. Without JavaScript, all four links and native room disclosures remain available.
- Numbered markers (01–03) use 44px native summary targets and concise plant notes. Desktop notes float beside markers; mobile notes occupy document flow below the image.
- Keep the closing catalog action a compact text link. Preserve visible keyboard focus and respect reduced-motion preferences for any local state transitions.

## Do's and Don'ts

- Preserve the bright room, sparse furniture and visible plants.
- Keep catalog, blog, account and favorite links functional.
- Keep suggestions framed as starting points; the site provides plant information and does not sell plants.
- Do not add sales claims, testimonials, stock metrics or continuous animation.
- Do not restore a photo-backed text hero, parallax, marker pulses or entrance animation on this landing.

## Blog Extension

The user prefers a Reddit-style community blog. `css/blog.css` loads only for `blog.php` and `blog-post.php`: a pale green-gray ground, white post surfaces, deep green actions and Be Vietnam Pro for headings, body text and controls. The landing keeps its own Lora typography. Existing post content and uploaded covers remain authoritative; the shared `blog_cover_url()` helper supplies existing indoor-plant photographs when a cover is absent.

The introduction and existing moving article strip occupy separate desktop columns. Preserve its pause, keyboard, touch and reduced-motion behavior. Posts always form one vertical feed, with author/date metadata first, compact right-hand thumbnails and up/down vote, save and comment controls. A topic sidebar sits to the right above 1050px and follows the feed below that breakpoint. Post surfaces use a 1px border and 12px corners without shadows. Search uses persistent labels and personal views use underlined tabs. Article text stays within 720px, and uploaded detail images use `object-fit: contain`. Use 44px action targets and 16px form text. Do not extend these route-specific styles to admin or other internal pages.
