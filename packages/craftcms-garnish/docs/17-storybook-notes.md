# 17 — Storybook notes

The interactive demos for `@craftcms/garnish` live in **Storybook**, replacing the
old single-page Vite `playground/`. This doc covers the setup, how to add a story
when you port a new component, and the shared event-log helper.

## Why Storybook, and why `html-vite`

The playground had grown to 12 demo sections in one ~900-line `main.ts`. Splitting
it into one story file per component keeps each component's demos isolated,
navigable, and documented (autodocs).

Garnish widgets are **imperative** — `new Modal(container)`,
`new DisclosureMenu(trigger)`, `new HUD(trigger, body)` — operating on plain DOM,
**not** web components. So we use the **`@storybook/html-vite`** renderer: each
story's `render()` returns an `HTMLElement` and instantiates the Garnish class
inside it. (By contrast, `@craftcms/cp` uses `@storybook/web-components-vite`
because its components are custom elements.) We otherwise match cp's Storybook:
version **10.4.1** and the **docs / a11y / themes** addons.

## Layout

```
packages/craftcms-garnish/
  .storybook/
    main.ts        # framework: @storybook/html-vite; stories glob; addons
    preview.ts     # global parameters + theme decorator; imports preview.css
    preview.css    # demo globals migrated from the old playground/styles.css
  stories/
    _log.ts        # shared event-log panel + drag-event wiring + layout helper
    _helpers.ts    # shared modal-container builders
    modal.stories.ts
    focus.stories.ts
    compat.stories.ts
    base-drag.stories.ts
    drag.stories.ts
    drag-drop.stories.ts
    drag-sort.stories.ts
    hud.stories.ts
    disclosure-menu.stories.ts
    utilities.stories.ts
```

Stories live in a **top-level `stories/`** directory (mirroring how `tests/` was
split out of `src/`), so `src/` stays implementation-only. The stories glob is
`../stories/**/*.stories.@(ts|mdx)`; helper modules are prefixed with `_` so they
are imported but never picked up as stories. Each story imports the **real source**
(`../src/index`, `../src/compat`) — never the built `dist/` — so changes
hot-reload.

`tsconfig.json` includes `stories` and `.storybook` so `npm run check:types`
type-checks them; Prettier's globs include them too.

## Scripts

```bash
npm run dev          # storybook dev -p 6006  (the dev harness)
npm run storybook    # alias of dev
npm run build:storybook  # static build (compiles every story — the CI proof)
```

`vite`/`vitest`/`tsdown` are unchanged; Storybook's `html-vite` builder uses Vite
under the hood and supplies its own pipeline (there is no package-root
`vite.config.ts` anymore — it only ever served the playground).

## The event-log helper

Drag/menu/modal stories surface the events the real widgets fire. `stories/_log.ts`
exports:

- `createEventLog(initialMessage?)` → `{panel, log, clear}`. Mount `panel` in the
  story; call `log(tag, message, isError?)` from your event handlers.
- `wireDragEvents(log, dragger, tag, label)` — subscribes a dragger's
  `dragStart`/`drag`/`dragStop` into the log, coalescing the per-RAF-frame `drag`
  to a single line per gesture.
- `storyLayout(main, log?)` — wraps the demo column and (optional) log panel in the
  `.pg-story` flex layout the global styles expect.

Typical story shape:

```ts
import type {Meta, StoryObj} from '@storybook/html-vite';
import {Modal} from '../src/index';
import {createEventLog, storyLayout} from './_log';

const meta: Meta = {title: 'Modal'};
export default meta;
type Story = StoryObj;

export const Basic: Story = {
  render: () => {
    const log = createEventLog('Modal story loaded.');
    const main = document.createElement('div');
    // …build controls, `new Modal(container)`, wire events to `log.log(...)`…
    return storyLayout(main, log);
  },
};
```

Use Storybook `args`/`argTypes` controls where they add value — e.g. Modal's
`hideOnEsc`/`hideOnShadeClick`/`closeOtherModals`, the draggable/resizable
toggles, HUD `preferredOrientation`, `Drag` drop mode, and DisclosureMenu
`withSearchInput`.

### Imperative widgets and the DOM

Modals, HUDs, and disclosure menus position themselves `fixed`/`absolute` and
move their own elements to `document.body`, so the demo containers are appended to
`document.body` rather than the story canvas (see `_helpers.ts`). `DisclosureMenu`
resolves its panel from the trigger's `aria-controls`; since a story builds its
DOM detached before Storybook mounts it, keep the menu panel as the trigger's
**next element sibling** with a **unique id** (the disclosure story uses a counter)
so resolution works and re-renders don't collide with panels already moved to
`<body>`.

## Adding a story for a new component

When you port a new component, **add `stories/<name>.stories.ts`** instead of a
playground section:

1. Create `stories/<component>.stories.ts` with a default `Meta` (`title`) and one
   or more `StoryObj` exports.
2. In each `render()`, build the demo DOM, instantiate the component from
   `../src/index`, and (if it fires events worth showing) wire them into a
   `createEventLog()` panel via `storyLayout`.
3. Reuse the `.pg-*` classes in `.storybook/preview.css`; add component-specific
   globals there if the widget renders its own elements (shade, tip, menu panel).
   Keep `touch-action: none` on any drag/menu handles.
4. Prefer `args`/`argTypes` over multiple near-duplicate buttons when a setting is
   the thing being demonstrated.
