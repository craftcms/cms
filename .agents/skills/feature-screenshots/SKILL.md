---
name: feature-screenshots
description: "Use when taking screenshots of a control panel feature for a PR, changelog, docs or announcement: a palette, modal, menu, card, field, or any other piece of UI. Covers isolating the feature, transparent PNGs with real shadows, focused crops, full-page shots, consistent sizes across a set, and adding them to a PR as a gallery."
---

# Feature Screenshots

Screenshots show one feature, not the page around it. Pick the style from the
feature's edges:

- **Transparent PNG** when the feature has distinct edges of its own, above
  all rounded corners or a box shadow: dialogs, palettes, popovers, menus,
  cards, chips, toasts. Only the feature and its shadow remain, so it sits on
  whatever background the PR, doc or post has.
- **Focused crop** otherwise: a field, a table, a row of buttons, part of a
  page. Crop to the feature with the same padding on all four sides, and hide
  the neighboring UI and text inside that padding.

Both use the same setup and isolation. `scripts/` holds the page helper
(`shot.js`) and the capture, matte and preview helpers.

## Setup

Use Chrome with the Claude in Chrome tools, and `screencapture` (macOS), which
captures at the display's pixel density, so Retina shots come out at 2×.

1. Load the page helper: paste all of `scripts/shot.js` into the page as one
   JavaScript call, with your options on its last line. It defines
   `__shot.set()`, `__shot.ready()` and `__shot.off()`, and titles the tab
   `SCREENSHOT-TARGET` for the scripts. Inertia visits keep it, but a full
   page load drops it; restore it and its options after every one:

   ```js
   (0, eval)(localStorage.__shotCode)
   ```

2. Size the window so its page area is a consistent 16:9, e.g. 1408×792 on a
   display that caps the window at 939 points tall. Every shot in a set uses
   the same page size, so the feature lands in the same place each time. The
   extension's `resize_window` may not change the page size; set the bounds
   with AppleScript instead, after `scripts/activate.sh` makes the tab's
   window window 1:

   ```bash
   osascript -e 'tell application "Google Chrome" to set bounds of window 1 to {0, 25, 1408, 900}'
   ```

   Then read `innerWidth` and `innerHeight`, and adjust the bounds by the
   difference until they match. Chrome's “started debugging this browser” bar
   takes about 56 points.
3. Get the page's screen origin; `capture.sh` takes screen points. Run
   `scripts/activate.sh` first: the extension's tab can sit behind another
   tab in its window, and switching to it can change `innerHeight`. Then read
   `innerHeight` from `__shot.ready()`, and pass it to `origin.sh`, which
   takes the window's position from Chrome and prints `x y`:

   ```bash
   scripts/origin.sh <innerHeight>
   ```

   Don't take it from the page's own `screenX`/`screenY`: in fullscreen or on
   another display, they report the wrong position. The debugging bar can
   also appear after a JavaScript call and push the page down, which an
   earlier `innerHeight` doesn't reflect. Before the first capture, check
   the page's position with a full-screen `screencapture -x`, and adjust. For
   a transparent PNG, a generous region absorbs the shift, since `matte.sh`
   trims to the feature.

4. Pick the theme with `__shot.set({theme: 'dark'})` (or `'light'`). It
   changes only the page, never the person's preference, and holds the theme
   against the control panel, which reapplies the preference while a page
   renders and on Inertia visits. Set it once the page has settled. Colors
   transition when the theme changes, so wait about 800ms before capturing.
   - **For a PR**, capture every shot in both themes, so it matches the
     reader's own GitHub theme. Keep the page size, crop and padding the same
     in both, and name the files `…-light.png` and `…-dark.png`.
   - **For a blog post, docs or marketing**, each shot uses one theme.
     Alternate them across a set, and show front-end previews, such as Live
     Preview, in light.
5. Show real, representative content: a seeded entry or asset, a search that
   returns a good mix of results.

Call `__shot.ready()` right before every capture. It reapplies the theme,
style and tab title, blurs focus rings (unless the `keepFocus` option is set),
and dispatches a `resize` so open popovers reposition.

Never confirm a destructive action to get a shot. Opening a confirmation is
fine, but leave it with Cancel, Back or Escape. Before typing, check from the
page that the intended input has focus, so keystrokes can't land in a field
and change content.

Avoid actions that can open a native `confirm()` or `alert()`. One blocks all
input from the extension, so its clicks time out after about 30 seconds. When
a click times out, look with a full `screencapture -x`. If dismissing the
dialog is harmless, press Escape (Cancel) or Return through Chrome:

```bash
osascript -e 'tell application "Google Chrome" to activate' \
  -e 'tell application "System Events" to key code 53' # Escape; `keystroke return` for Return
```

### Before each capture

- No widows in visible text. Narrow panes cause them; closing the details
  pane widens the content.
- No truncated labels where a wider column or shorter content avoids them.
- No “Untitled entry” or other test records, and dates that read naturally.
- No focus rings unless they're the point, including inside preview iframes;
  `__shot.ready()` blurs those too.
- The pointer parked outside the capture region (see below).
- The same window size as the rest of the set.

## Isolating the feature

Hide everything but the feature, over a background color:

```js
__shot.set({feature: '.command-palette', bg: '#000'});
```

`feature` is a selector for the feature's outermost element; `bg` is any CSS
color, such as `'var(--c-surface-default)'`. Set `feature: null` to show the
page again.

- `visibility` inherits into shadow roots, and popovers and tooltips lifted
  into the top layer stay inside their DOM subtree. So a menu or tooltip that
  belongs to the feature stays visible.
- The helper clears dialog backdrops and hides the caret, which keeps two
  captures of the same state identical.
- A tooltip or menu that extends past the feature's edges gets cut off by the
  crop. Leave it out, or capture a larger region.
- For a state that only shows on hover, such as a nav flyout, set the
  component's state instead of hovering, since hovering draws the
  extension's cursor: `navItem.flyoutOpen = true` on a `craft-nav-item`.
- For one-off rules, pass `css`: `__shot.set({css: '…'})`.

### Keep the extension's overlays out

While the Claude in Chrome extension drives a tab, it draws a glowing border
around the page's edges and its own cursor where the last click or hover
was. Neither is in the page's DOM, so CSS can't hide them, and
`screencapture -x` records both: it only leaves out the system cursor.

- Before every capture, hover somewhere outside the capture region, such as
  the inset margin's bottom-right corner, unless you're showing a hover
  state. Clicking or hovering moves the drawn cursor there.
- Keep the capture region about 24px clear of the window's edges. For a
  feature that sits against an edge, such as a slideout, a sidebar or the
  header, move it in for the shot and capture it there:

  ```js
  feature.style.translate = '-24px 24px'; // a slideout against the top and right edges
  ```

- For a full-page shot, inset the whole page by 24px with
  `__shot.set({inset: true})`, and capture the inset area: the page origin
  plus 24, `innerWidth - 48` by `innerHeight - 48`. The page's own fixed
  elements move in with it, and the helper also fits what sizes itself from
  the window or lives outside `#app`: the sidebar and details pane, toasts,
  Live Preview, and fullscreen dialogs. A component that measures the window
  itself, such as the image editor, still overflows; check the shot's edges.

Call `__shot.off()` when done, which restores the title and theme, undo any
`translate`, and close any tabs you opened.

## Transparent PNG

Screen capture can't record transparency, so capture the same state twice,
over pure black and over pure white, and recover each pixel's opacity from
the difference. Shadows come out as real partial transparency.

1. Find the feature's rect from the page, and choose a capture region around
   it with room for the whole shadow, which can extend 20–40px below.
2. `__shot.set({feature: '…', bg: '#000'})` and `__shot.ready()`, then
   capture:

   ```bash
   scripts/capture.sh black.png <x> <y> <w> <h>
   ```

3. `__shot.set({bg: '#fff'})` and `__shot.ready()`, and capture the same
   region to `white.png`.
4. Combine them:

   ```bash
   scripts/matte.sh black.png white.png feature.png [W:H:X:Y]
   ```

   Without a crop, the result is trimmed to the visible pixels plus 8px. For a
   set, work out the crop once and pass it to every shot, so they're all the
   same size.

   `matte.sh` prints the highest alpha on each edge. All four must be 0;
   anything higher means the region cut something off. Capture a larger one.
5. Check it over a bright color, which shows fringing, a clipped shadow, or
   anything left behind the feature:

   ```bash
   scripts/preview.sh feature.png preview.png
   ```

   Then look at `preview.png`.

## Focused crop

1. Isolate the feature over the color it normally sits on, e.g.
   `bg: 'var(--c-surface-default)'`, which follows the theme.
2. Add the same padding to all four sides of the feature's rect. Around 24 CSS
   pixels suits most features; use the same value across a set.
3. Capture that region once with `capture.sh`. Nothing but the feature
   remains inside the padding.

## Phone-width shots

Chrome won't size a window as narrow as a phone. Overlay a same-origin iframe
of the page at phone width instead: media and container queries respond to
the iframe's own width, so it lays out as it would on a phone.

```js
const frame = document.createElement('iframe');
frame.id = 'shot-phone';
frame.src = location.href;
Object.assign(frame.style, {
  position: 'fixed', left: '509px', top: '48px', width: '390px',
  height: '680px', border: '0', zIndex: '2147483647',
  borderRadius: '28px',
  boxShadow: '0 0 0 1px rgba(0,0,0,.12), 0 8px 24px rgba(0,0,0,.18)',
});
document.body.append(frame);
await new Promise((resolve) => (frame.onload = resolve));
frame.contentDocument.documentElement.dataset.theme = 'light'; // or 'dark'
```

- Isolate it with `#shot-phone` as the feature, and capture it as a
  transparent PNG, so the rounded corners and shadow read as a phone screen.
- Scroll and query inside it through `frame.contentDocument`.
- Keep the iframe and its shadow about 24px clear of the window's edges.
- Remove it when you're done.

## Thumbnails

For a thumbnail at a fixed size, such as 2000×1200 for a post, crop the shot
to that aspect ratio, then scale it. `sips` does both, where ImageMagick
isn't installed. Its sizes and offsets take height first; from a 2720×1496
shot:

```bash
sips -c 1440 2400 --cropOffset 28 160 shot.png --out thumb.png # H W, then Y X
sips -z 1200 2000 thumb.png
```

## Adding them to a PR

Only add screenshots to a PR by uploading them to github.com as below, which
needs the person's signed-in GitHub session in Chrome. Every image in a PR must
be a `https://github.com/user-attachments/assets/…` URL. Never host them
anywhere else: not in the repository (no commits, branches or custom refs such
as `refs/screenshots/…` to link `raw` URLs from), and not on another service,
such as an agent tool's artifact or file host. If you can't upload them, leave
them out and say so.

1. Name the files for what they show, such as `command-palette-docs.png`.
   GitHub uses the file name as the image's alt text.
2. Upload them through the PR's comment box. Open the PR in a new tab, find
   the comment box's file input, and upload with the `file_upload` tool. It
   only reads from folders this session can access, such as the scratchpad.

   The input (`#fc-new_comment_field`) is hidden, so `find` may only offer the
   "Add files" button, which `file_upload` rejects. Expose the input to the
   accessibility tree for the upload, then put it back:

   ```js
   const input = document.getElementById('fc-new_comment_field');
   input.dataset.shotStyle = input.getAttribute('style') ?? '';
   input.setAttribute('aria-label', 'Screenshot upload target');
   input.style.cssText =
     'position:fixed;inset-block-start:0;inset-inline-start:0;width:40px;height:40px;opacity:0.01;z-index:99999;display:block';
   ```

   Then `find` "file input labeled Screenshot upload target" and upload to
   it. Afterwards, restore it:

   ```js
   input.setAttribute('style', input.dataset.shotStyle);
   input.removeAttribute('aria-label');
   ```
3. Wait a few seconds, then read each upload's
   `https://github.com/user-attachments/assets/…` URL from the comment
   textarea.
4. Clear the textarea and never submit the comment:

   ```js
   for (const t of document.querySelectorAll('textarea')) {
     if (t.value.includes('user-attachments')) {
       t.value = '';
       t.dispatchEvent(new Event('input', {bubbles: true}));
     }
   }
   window.onbeforeunload = null;
   ```

5. Put them in the description with `gh pr edit --body-file`, as a `<picture>`
   per shot, so GitHub shows the one matching the reader's theme:

   ```html
   <picture>
     <source
       media="(prefers-color-scheme: dark)"
       srcset="https://github.com/user-attachments/assets/…"
     >
     <source
       media="(prefers-color-scheme: light)"
       srcset="https://github.com/user-attachments/assets/…"
     >
     <img
       alt="The command palette, searching “image”"
       src="https://github.com/user-attachments/assets/…"
       width="640"
     >
   </picture>
   ```

   The `<img>` uses the light shot, as the fallback. Set its `width` to exactly
   half the image file's pixel width, since `screencapture` shoots at 2× on
   Retina displays, so it isn't shown larger than the UI it was taken from.
   Read it from the file with `sips -g pixelWidth shot.png`, never from the
   size GitHub happens to display it at. Leave `height` off: GitHub caps an
   image's width to fit its container but keeps a set height, which squashes
   it. Give it alt text that says what the shot shows.

   For a gallery of more than two shots, use a two-column table with a
   caption under each shot:

   ```html
   <table>
   <tr>
   <td width="50%" valign="top">
   <picture>…</picture>
   <br />The default state on an entry.
   </td>
   <td width="50%" valign="top">
   <picture>…</picture>
   <br />Searching “image”.
   </td>
   </tr>
   </table>
   ```

   Each shot is scaled down to fit its cell. Unlike a bare `<img>`, GitHub
   doesn't link a `<picture>` to the full-size file, so readers can't click a
   shot to enlarge it.

6. Open the PR page to check the images render.

Replacing a screenshot means uploading a new one; uploads can't be edited.
