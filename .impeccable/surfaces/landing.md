# Landing: index.php

Mode: Persuade.

THESIS: A calm photographic promenade moves visitors from imagining greenery in a room to exploring species by light, placement, category, and community care notes—refusing generic SaaS hero cards and bloated FAQ stacks.

OWN-WORLD: Committed Floema (`https://floema.com/en`) visual system translated for Vũ Điệu Rừng Xanh in Vietnamese: warm ivory paper (`#f2efea`) and secondary `#ebe7df` split surfaces, deep charcoal ink (`#241f21`), deep forest green (`#042d2b`) narrative band, iconic flour-yellow (`#e9e778`) architectural footer with 1px botanical line art, category accent pills (`#f76c46`, `#bacfa3`, `#85a1c5`, `#c6af88`), soft dappled leaf shadows, adaptive floating pill navigation, and Be Vietnam Pro display/body typography.

STORY: Minimalist 3D photographic hero -> full-bleed interactive 3-plant room scene -> editorial manifesto with scroll-lit lines & real recent blog stories -> interactive light/position plant finder with 50/50 botanical showcase -> centered typographic collection directory with inline thumbnails & live species counts -> deep forest green brand/advisor narrative -> 3-column staggered care pillars & bold closing invitation -> flour-yellow architectural footer.

FIRST VIEWPORT: Full-viewport centered display headline (`Dành một góc cho màu xanh.`) over `#f2efea` surrounded by 12 distinct floating plant tiles at varied depths and tinted mattes, with zero center CTA button and a quiet bottom exploration baseline (`Cuộn để khám phá ↓` and live catalog count).

FORM: Native PHP/HTML, route-scoped `css/landing.css`, and vanilla `js/landing.js`. Built directly in `index.php` per explicit user confirmation replacing the lower FAQ/sage bands with Floema's full section cadence and palette. Real database plant statistics, category counts, and recent blog posts remain authoritative.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance.

## Interaction and motion

- Hero 12-tile gallery applies smooth passive scroll displacement (`--hero-travel`) and subtle pointer parallax (`--hero-mx`, `--hero-my`) bounded to hero height; reduced motion and no-JS keep a crisp static composition.
- Adaptive sticky navigation detects dark (`#042d2b`) and yellow (`#e9e778`) sections via scroll/IntersectionObserver and swaps pill themes cleanly.
- Room scene retains native `<details>` keyboard access, exclusive selection, Escape with focus restoration, and outside click dismissal.
- Editorial manifesto smoothly illuminates clause ink from `#7a716d` to `#241f21` as the reader scrolls; reduced motion and no-JS show full `#241f21` contrast at rest.
- Plant finder filters the 4 editorial showcase panels live, announces match counts (`role="status"`), handles empty state, and submits via GET to `plant-advisor.php`.

## Assets

- Existing `images/indoor-room.png` (`images/optimized/indoor-room.webp`) anchors the full-bleed room scene.
- Verified clean local plant photographs (`anh4`, `anh14`, `anh21`, `anh22`, `anh23`, `anh25`, `anh27`, `anh28`, `anh29`, `anh35`, `anh47`, `anh48`, `anh49`, `anh57`, `anh58`) supply all hero tiles, split showcase panels, category inline thumbs, dark forest feature, and care pillars without watermarks or duplicate tiles.
- Inline botanical SVG line art and soft CSS/SVG leaf-shadow layers follow 1.3px–1.5px stroke craft without extra image dependencies.

## Verification boundary

These notes reflect the current design direction; check current test execution (`tests/run.php`, `tests/plant-advisor.php`, `tests/landing-ui.cjs`) and live browser screenshots for verified layout and runtime behavior.
