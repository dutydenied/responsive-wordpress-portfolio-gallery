# Portfolio Gallery

A lightweight WordPress plugin for a design/illustration portfolio: a responsive gallery grid with tag filtering, a lightbox preview, and a link through to each piece's own page. No jQuery, no external libraries.

## What it adds

- **Portfolio Item** — a custom post type. Each piece gets its own title, description, and featured image.
- **Portfolio Tag** — a non-hierarchical taxonomy (e.g. *Digital Design*, *UI/UX*, *Illustration*). The same tags double as the filter buttons and the category labels shown on each item.
- **`[portfolio_gallery]`** shortcode — renders the filter bar + grid wherever it's placed.

## Usage

```
[portfolio_gallery]
[portfolio_gallery tag="illustration"]   pre-filtered to one tag on load
[portfolio_gallery limit="12"]           cap how many items load (default: all)
```

Add items under the **Portfolio Items** admin menu; assign one or more **Tags** per item.

## Gallery behavior

- **Responsive grid**: `repeat(auto-fill, minmax(220px, 1fr))` — the browser fits as many 220px+ columns as the screen allows and stretches them evenly. No breakpoints to maintain; a phone gets 1–2 columns, a wide desktop gets 6+.
- **Filtering**: clicking a tag button toggles the `hidden` attribute on non-matching items. Because CSS Grid removes hidden items from layout, the rest reflow automatically — no masonry library needed.
- **Lightbox**: clicking an image opens a full-size preview (title, tags, and its own "See more info" link) without leaving the grid. Closes via the ✕ button, backdrop click, or Escape; focus returns to the image that opened it.
- **"See more info" link**: separate from the lightbox trigger, goes straight to the item's own single page.

## Image sizes

Two custom sizes are registered, both proportional (no hard crop, so illustrations of any aspect ratio aren't cut off):

| Size | Width | Used for |
|---|---|---|
| `portfolio_grid` | 600px | Grid thumbnails |
| `portfolio_lightbox` | 1600px | Lightbox preview |

**Important:** these sizes are only generated for images uploaded *after* the plugin is active. If you're adding this to a site with existing Portfolio Items, run a plugin like [Regenerate Thumbnails](https://wordpress.org/plugins/regenerate-thumbnails/) once so older images get the new sizes too.

## Files

```
portfolio-gallery.php            Post type, taxonomy, image sizes, shortcode
assets/portfolio-gallery.css     Grid, filter bar, lightbox styles
assets/portfolio-gallery.js      Filtering + lightbox behavior (vanilla JS)
```

## Notes

- Single item pages currently use the active theme's default template. For a custom layout, add a `single-portfolio_item.php` template to your theme.
- Assets only load on pages/posts that use the `[portfolio_gallery]` shortcode.
