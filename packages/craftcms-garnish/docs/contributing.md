# Contributing

This guide is for working **on** `@craftcms/garnish` (rather than consuming it). It
covers the toolchain, the scripts you'll run, the verification gates, and how the
interactive demos are organized.

## Toolchain

The package is TypeScript, ESM-only, and jQuery-free at its core. The build uses
[Vite+ Pack](https://viteplus.dev/guide/pack) (producing the dual `.` + `/compat` entries), tests run
on [Vitest](https://vitest.dev) against [happy-dom](https://github.com/capricorn86/happy-dom),
and interactive demos run in [Storybook](https://storybook.js.org).

## Scripts

```bash
pnpm run dev          # Storybook dev server at http://localhost:6006
pnpm run storybook    # alias of `pnpm run dev`
pnpm run build:storybook  # static Storybook build (compiles every story — the CI proof)
pnpm run build        # production build (dual `.` + `/compat` entries)
pnpm run build:watch  # Vite+ pack watch build
pnpm run test         # Vitest suite (one run)
pnpm run test:dev     # Vitest watch mode
pnpm run test:coverage  # Vitest with V8 coverage
pnpm run check:types  # tsc --noEmit (includes stories + .storybook)
pnpm run check:format # Oxfmt check
pnpm run format       # Oxfmt write (./src ./tests ./stories ./.storybook)
pnpm run lint         # check:types + check:format
```

## Verification gates

Before opening a PR, confirm the gates are green:

```bash
pnpm run check:types && pnpm run test && pnpm run build
```

Run `pnpm run format` to apply Oxfmt; CI uses `check:format`. New behavior needs
test coverage — the suite favors **real code paths** over heavy mocking, and uses the
happy-dom environment. Note that happy-dom has no layout engine, so
`offsetWidth`/`offsetHeight` are always `0`; tests that depend on element visibility
(e.g. the focusable matcher) stub `getClientRects`. Real browsers need no such stub.

## Storybook (interactive demos)

The interactive demos for `@craftcms/garnish` live in **Storybook**, with one story
file per component. Stories import the **real source** (`../src`), so edits
hot-reload.

### Why Storybook, and why `html-vite`

Splitting demos into one story file per component keeps each component's demos
isolated, navigable, and documented (autodocs).

Garnish widgets are **imperative** — `new Modal(container)`,
`new DisclosureMenu(trigger)`, `new HUD(trigger, body)` — operating on plain DOM,
**not** web components. So we use the **`@storybook/html-vite`** renderer: each
story's `render()` returns an `HTMLElement` and instantiates the Garnish class
inside it. (By contrast, `@craftcms/ui` uses `@storybook/web-components-vite`
because its components are custom elements.) We otherwise match cp's Storybook:
version **10.5.5** and the **docs / a11y / themes** addons.

## Layout

```
packages/craftcms-garnish/
  .storybook/
    main.ts        # framework: @storybook/html-vite; stories glob; addons
    preview.ts     # global parameters + theme decorator; imports preview.css
    preview.css    # demo globals migrated from the old playground/styles.css
  stories/
    _log.ts        # Actions-panel event logger + drag-event wiring + layout helper
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

`tsconfig.json` includes `stories` and `.storybook` so `pnpm run check:types`
type-checks them; Oxfmt's globs include them too.

Storybook's `html-vite` builder uses Vite under the hood and supplies its own
pipeline, so there is no package-root `vite.config.ts`.

## The event logger (Storybook Actions)

Drag/menu/modal stories surface the events the real widgets fire by logging them
to Storybook's built-in **Actions** panel (the "Actions" tab in the addons panel),
rather than a hand-rolled in-canvas panel. `stories/_log.ts` wraps
`storybook/actions` and exports:

- `createEventLog()` → `{log}`. Call `log(tag, message)` from your event handlers;
  each `tag` becomes a named action so the panel groups events by tag (e.g.
  `modal`, `drag`, `disclosure`). No DOM panel to mount.
- `wireDragEvents(log, dragger, tag, label)` — subscribes a dragger's
  `dragStart`/`drag`/`dragStop` into the log, coalescing the per-RAF-frame `drag`
  to a single line per gesture so the panel stays readable.
- `storyLayout(main)` — tags the demo element with `.pg-story` for the global
  styles; the canvas is just the demo.

The Actions panel is part of Storybook core in 10.x — no `@storybook/addon-actions`
entry is needed in `.storybook/main.ts`; importing `action` from
`storybook/actions` is enough.

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
    const log = createEventLog();
    const main = document.createElement('div');
    // …build controls, `new Modal(container)`, wire events to `log.log('modal', …)`…
    return storyLayout(main);
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

When you add a new component, **add `stories/<name>.stories.ts`** alongside it:

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
