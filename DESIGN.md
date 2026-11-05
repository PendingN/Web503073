---
name: Vũ Điệu Rừng Xanh — Indoor Living
description: Bright, airy, minimal indoor plants and home decor.
colors:
  paper: "#faf9f6"
  ink: "#243b30"
  muted: "#626b60"
  primary: "#34533c"
  sage: "#edf0e8"
  line: "#dedfd6"
typography:
  display:
    fontFamily: "Lora, Georgia, serif"
    fontSize: "clamp(42px, 4.5vw, 72px)"
    fontWeight: 400
    lineHeight: 1.2
    letterSpacing: "-0.035em"
  body:
    fontFamily: "Be Vietnam Pro, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.95
rounded:
  control: "4px"
  disclosure: "12px"
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

Scope: `index.php` and its landing-only stylesheet, with the Blog extension described below. The user chose a minimal, bright, airy room with few objects, and wants visitors to find plants suitable for their own room. Other internal and admin pages retain their current design.

## Colors

Off-white is the page ground. Deep green carries text and actions; pale sage separates the finder and care guidance. Plant foliage supplies the main color. Use the `--room-*` tokens in `css/landing.css`.

## Typography

Self-hosted Lora regular and italic carry headings. Existing Be Vietnam Pro carries navigation, body copy and controls. The Lora license is included in `fonts/lora-OFL.txt`. Preserve Vietnamese diacritics.

## Layout

The hero lets a photographic room fill the viewport behind left-aligned copy. Three native disclosures identify plants in the scene. Content uses a 90% container capped at 1296px. The finder filters four editorial starting points by light and placement; links lead to the existing indoor catalog search.

At 700px and below, copy precedes the room image. Plant notes expand below the image. The finder stacks and plants use two columns. Larger layouts retain four columns. Body copy remains readable; native selects use 16px on mobile to avoid input zoom.

## Elevation & Depth

Keep ordinary sections flat. Use soft shadows only to lift scene markers and desktop plant notes. The room photo supplies natural depth. Pointer motion is bounded to 5px horizontally and 3px vertically, disabled for touch and reduced motion.

## Shapes

Rectangular photographs, restrained 4px control corners, circular 44px scene markers and 12px desktop note corners. Avoid enclosing every section in a card.

## Components

- Native `details` provide plant and FAQ disclosures. JavaScript adds exclusive selection, Escape, and outside dismissal for the room.
- A form filters editorial suggestions locally and announces the result count. Without JavaScript, all four links and native room disclosures remain available.
- A short room entrance and three marker pulses form the opening motion. Honor changes to `prefers-reduced-motion` immediately.

## Do's and Don'ts

- Preserve the bright room, sparse furniture and visible plants.
- Keep catalog, blog, account and favorite links functional.
- Keep suggestions framed as starting points; the site provides plant information and does not sell plants.
- Do not add sales claims, testimonials, stock metrics or continuous animation.

## Blog Extension

The user prefers a Reddit-style community blog. `css/blog.css` loads only for `blog.php` and `blog-post.php`: a pale green-gray ground, white post surfaces, deep green actions and Be Vietnam Pro for headings, body text and controls. The landing keeps its own Lora typography. Existing post content and uploaded covers remain authoritative; the shared `blog_cover_url()` helper supplies existing indoor-plant photographs when a cover is absent.

The introduction and existing moving article strip occupy separate desktop columns. Preserve its pause, keyboard, touch and reduced-motion behavior. Posts always form one vertical feed, with author/date metadata first, compact right-hand thumbnails and up/down vote, save and comment controls. A topic sidebar sits to the right above 1050px and follows the feed below that breakpoint. Post surfaces use a 1px border and 12px corners without shadows. Search uses persistent labels and personal views use underlined tabs. Article text stays within 720px, and uploaded detail images use `object-fit: contain`. Use 44px action targets and 16px form text. Do not extend these route-specific styles to admin or other internal pages.
