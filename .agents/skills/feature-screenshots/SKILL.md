---
name: feature-screenshots
description: "Use when taking screenshots of a control panel feature for a PR, changelog, docs or announcement: a palette, modal, menu, card, field, or any other piece of UI. Covers isolating the feature, transparent PNGs with real shadows, focused crops, consistent sizes across a set, and adding them to a PR as a gallery."
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

Both use the same setup and isolation. `scripts/` holds the capture, matte
and preview helpers.

## Setup

Use Chrome with the Claude in Chrome tools, and `screencapture` (macOS), which
captures at the display's pixel density, so Retina shots come out at 2×.

1. Size the window so its page area is a consistent 16:9, e.g. 1408×792 on a
   display that caps the window at 939 points tall. Every shot in a set uses
   the same page size, so the feature lands in the same place each time.
2. Get the page's screen origin from the page; `capture.sh` takes screen
   points:

   ```js
   [screenX, screenY + outerHeight - innerHeight]
   ```

3. Capture every shot in both themes, light and dark, so it matches the
   reader's own GitHub theme. Switch the page's theme without touching the
   person's preference:

   ```js
   document.documentElement.dataset.theme = 'dark'; // dark
   delete document.documentElement.dataset.theme;   // light
   ```

   Keep the page size, crop and padding the same in both, and name the files
   `…-light.png` and `…-dark.png`.
4. Show real, representative content: a seeded entry or asset, a search that
   returns a good mix of results.

Never confirm a destructive action to get a shot. Opening a confirmation is
fine, but leave it with Cancel, Back or Escape. Before typing, check from the
page that the intended input has focus, so keystrokes can't land in a field
and change content.

## Isolating the feature

Hide everything but the feature with a style tag, and mark the tab so the
capture script can bring its window to the front:

```js
window.__iso = (bg) => {
  let s = document.getElementById('shot-isolate');
  if (!s) {
    s = document.createElement('style');
    s.id = 'shot-isolate';
    s.textContent = `
      html, body { background: var(--shot-bg) !important; background-image: none !important; }
      body * { visibility: hidden !important; }
      FEATURE, FEATURE * { visibility: visible !important; }
      input, textarea { caret-color: transparent !important; }
      #claude-agent-glow-border, #claude-phantom-cursor { display: none !important; }
    `;
    document.head.append(s);
  }
  document.documentElement.style.setProperty('--shot-bg', bg);
  window.__title ??= document.title;
  document.title = 'SCREENSHOT-TARGET';
};
window.__unIso = () => {
  document.getElementById('shot-isolate')?.remove();
  document.documentElement.style.removeProperty('--shot-bg');
  if (window.__title) document.title = window.__title;
};
```

Replace `FEATURE` with the feature's outermost element.

- `visibility` inherits into shadow roots, and popovers and tooltips lifted
  into the top layer stay inside their DOM subtree. So a menu or tooltip that
  belongs to the feature stays visible.
- A dialog's `::backdrop` is not hidden by this. Clear it too, e.g.
  `craft-dialog::part(dialog)::backdrop { background: transparent !important; }`.
- Hiding the caret keeps two captures of the same state identical.
- Blur any focus ring that isn't part of what you're showing
  (`document.activeElement.blur()`), such as a view heading focused on open.
- Move the pointer off the feature, unless you're showing a hover state.
  `screencapture -x` leaves the cursor out.
- A tooltip or menu that extends past the feature's edges gets cut off by the
  crop. Leave it out, or capture a larger region.

Call `__unIso()` when done, and close any tabs you opened.

## Transparent PNG

Screen capture can't record transparency, so capture the same state twice,
over pure black and over pure white, and recover each pixel's opacity from
the difference. Shadows come out as real partial transparency.

1. Find the feature's rect from the page, and choose a capture region around
   it with room for the whole shadow, which can extend 20–40px below.
2. `__iso('#000')`, then capture:

   ```bash
   scripts/capture.sh black.png <x> <y> <w> <h>
   ```

3. `__iso('#fff')`, and capture the same region to `white.png`.
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

1. `__iso(bg)` with the color the feature normally sits on, e.g. the page's
   `--c-surface-default`, read from the page in each theme.
2. Add the same padding to all four sides of the feature's rect. Around 24 CSS
   pixels suits most features; use the same value across a set.
3. Capture that region once with `capture.sh`. Nothing but the feature
   remains inside the padding.

## Adding them to a PR

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
       height="360"
     >
   </picture>
   ```

   The `<img>` uses the light shot, as the fallback. Set its `width` and
   `height` to exactly half the image file's pixel size, since `screencapture`
   shoots at 2× on Retina displays, so it isn't shown larger than the UI it was
   taken from. Read the size from the file with
   `sips -g pixelWidth -g pixelHeight shot.png`, never from the size GitHub
   happens to display it at. Give it alt text that says what the shot shows.

   For a gallery of several shots, stack the `<picture>`s one after another,
   each followed by a blank line and a one-line caption. Don't put them in a
   multi-column table. A table cell caps each image's width but not its
   `height`, which squashes it, and leaving `height` off only shrinks the
   shot. Unlike a bare `<img>`, GitHub doesn't link a `<picture>` to the full
   size, so readers can't open a shrunken shot any larger.

6. Open the PR page to check the images render.

Replacing a screenshot means uploading a new one; uploads can't be edited.
