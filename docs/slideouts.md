# Slideouts

Slideouts render a CP screen in a panel over the current page. A slideout request returns a normal
Inertia response, so **the same page component renders as either a full page or a slideout** — only
the shell around it differs.

Screens that haven't been ported to a Vue page still work: they fall back to a built-in component
that draws the server-rendered HTML the response already carries.

> This is the Vue/Inertia system. The legacy `Craft.CpScreenSlideout` (jQuery/Garnish, in
> `resources/js/modules/slideout/`) still exists and is unchanged — see
> [Coexisting with the legacy stack](#coexisting-with-the-legacy-stack).

## Basic Usage

Open any CP URL in a slideout:

```vue
<script setup lang="ts">
  import {useSlideoutOpener} from '@/common/slideouts';

  const {open} = useSlideoutOpener();

  function editEntry(url: string, event: MouseEvent) {
    open(url, {opener: event.currentTarget as HTMLElement});
  }
</script>
```

`useSlideoutOpener()` returns `{open, closeAll}` and can be called outside `setup()`, so plain event
handlers and non-Vue code can use it too.

The same functions are on `window.Craft`, which is handy from the console:

```js
Craft.openSlideout('/admin/content/entries/news/5-hello');
Craft.closeSlideout(id);
Craft.closeAllSlideouts();
```

> These globals are registered when the Inertia CP boots, so they only exist on Inertia pages. On a
> page still served by the legacy stack (the dashboard, for example) `Craft.openSlideout` is
> undefined.

## Opening

### `openSlideout(href, options?)`

Fetches `href` as an Inertia page and mounts it in a panel. Returns the `SlideoutInstance`.

| Option | Type | Description |
| --- | --- | --- |
| `opener` | `HTMLElement \| null` | Element to refocus when the panel closes, and what the [stacking rules](#stacking-and-nesting) are resolved against. Defaults to whatever had focus when it opened. |
| `width` | `string` | Width for this panel, as any CSS **length**. Defaults to `--slideout-width`. |

Pass `opener` whenever you have it — focus restoration and nesting both depend on it.

### Other exports

```ts
import {
  openSlideout,
  closeSlideout,   // (id: string) => void
  closeAllSlideouts,
  useSlideout,
  useSlideoutOpener,
} from '@/common/slideouts';
```

## Inside a slideout

`useSlideout()` returns the panel the calling component is in, or `null` on a full page:

```vue
<script setup lang="ts">
  import {useSlideout} from '@/common/slideouts';

  const slideout = useSlideout();

  function onSaved() {
    slideout?.close();
  }
</script>
```

| Member | Description |
| --- | --- |
| `instance` | The `SlideoutInstance` (`id`, `href`, `props`, `loading`, `error`, …) |
| `close()` | Closes this panel, and anything nested inside it |
| `reload()` | Re-fetches the screen |

To branch on context without caring about the panel itself:

```ts
import {useIsSlideout} from '@/common/composables/screen';

if (useIsSlideout()) {
  // …
}
```

## Making a screen work in a slideout

**Any `CpScreenResponse` already works.** No controller changes are needed.

```php
return (new CpScreenResponse())
    ->title($entryType->name)
    ->contentTemplate('…')
    ->action('entry-types/save');
```

Requesting that screen with the slideout headers returns an Inertia page. If the response has no
`inertiaPage()`, the component is `cp/Screen`, which renders the response's HTML fragments
(`content`, `details`, `tabs`, `contentNotice`, `errorSummary`, `toolbar`) into the shell's slots.

Porting a screen to a real Vue page is then just:

```php
->inertiaPage('settings/entry-types/Edit', $viewModel)
```

That same component now serves the full page and the slideout.

### How a request is routed

`CpScreenResponse::toResponse()` treats a JSON-accepting request as a slideout — the convention
Craft 5 established — and picks the wire format from `X-Inertia`:

| Caller | `Accept` | `X-Inertia` | Result |
| --- | --- | --- | --- |
| Inertia page visit | `text/html` | yes | Full page |
| Legacy `CpScreenSlideout` | `application/json` | no | Flat HTML payload (unchanged) |
| Vue slideout | `application/json` | yes | Inertia page object |

Inertia's own client sends `Accept: text/html`, so an ordinary page visit can never be mistaken for
a slideout.

Both formats are built from one pass over the screen, under a per-request input namespace keyed to
the `X-Craft-Container-Id` header — so two slideouts of the same screen don't collide on input
names.

### Detecting a slideout server-side

Unchanged from Craft 5 — the request accepts JSON:

```php
if ($this->request->hasHeader('X-Craft-Container-Id')) {
    // Rendering into a slideout.
}
```

## Saving

Saving works in a slideout without navigating, on both kinds of screen. No controller changes are
needed: `RespondsWithFlash` already answers JSON whenever the request accepts it, so `asSuccess()`
and `asFailure()` do the right thing on their own.

> Failures come back as **400**, not Laravel's usual 422 — `asJsonFailure()` picks it. Anything
> reading the status directly needs to expect that.

### Vue pages

Nothing to do. `useSettingsSave` detects the slideout and swaps its own submit strategy:

| | Full page | Slideout |
| --- | --- | --- |
| Transport | navigating `form.submit()` | direct `axios` post |
| `redirect` in payload | sent | omitted — a panel closes instead |
| On success | follows the redirect | closes the panel, reloads the page behind |
| On failure | Inertia error bag | `form.setError()` from the 400 body |

The elevated-session (423) retry, `transform`, and `elevatedFields` behave identically in both.

<kbd>Cmd</kbd>/<kbd>Ctrl</kbd>+<kbd>S</kbd> is "save and continue editing" — it saves and **keeps the
panel open**. The Save button closes it. Internally that's the `redirect: false` flag, which is why
`SlideoutScreen` deliberately doesn't pass it.

### Server-rendered screens

Screens without an `inertiaPage()` have no Vue form — just markup — so the shell submits its own
`<form>`. `SlideoutScreen` shows a Save button whenever the response carries a `screen.action`, and
posts the panel's inputs with `submitScreenForm()`.

Those inputs are already namespaced per panel (`ns[title]`, `ns[action]`, …), so the request carries
`X-Craft-Namespace` and `ExtractNamespace` un-prefixes them server-side back to what the controller
expects. That middleware runs *before* `HandleActionRequest`, so the un-namespaced `action` routes
correctly.

Errors render through the same `ErrorSummary`, flattened to one message per field by
`firstMessages()` so both paths look identical.

> **Element editor caveat.** An entry saved this way is written **directly** — drafts, autosave and
> provisional drafts still belong to `Craft.ElementEditor`, which isn't wired into the Vue panel.
> That differs from the legacy slideout on that screen.

### Unsaved changes

Discarding a panel with unsaved edits prompts first. That covers the close button, Cancel,
<kbd>Esc</kbd>, the shade — and **replacing**, which is the easy one to miss: opening a slideout
drops whatever is stacked above its opener, so double-clicking a second row on an index would
otherwise throw away an edited panel with no warning. Declining leaves everything untouched, and
`openSlideout()` resolves to `null`.

Closing a panel with dirty children asks once for the whole subtree, not once per panel.

How "dirty" is decided depends on the screen:

| Screen | Signal | Notes |
| --- | --- | --- |
| Vue page | Inertia's `form.isDirty` | Precise — reverting an edit clears it |
| Server-rendered | any `input`/`change` in the panel | Conservative; `Craft.initUiElements()` rewrites inputs after load, so snapshot-diffing the markup would read as user edits |

A close that follows a successful save passes `{force: true}` and never prompts — Inertia only
clears `isDirty` when a form's defaults are updated, so the panel still looks dirty at that moment.

Register a check yourself with `setSlideoutDirtyCheck(id, () => boolean)`; `SlideoutScreen` does
this automatically and clears it when the panel closes.

## Writing a page that works in both contexts

Pages render inside a **shell**. `AppLayout` is a dispatcher that picks one:

- `PageScreen` — the full CP shell (global nav, breadcrumbs, sidebar, footer)
- `SlideoutScreen` — the panel shell (title bar, tabs, body, save/cancel footer)

Both implement the same contract in `resources/js/common/layouts/screens/types.ts`, so a page needs
no branching. Configure the shell the usual way:

```vue
<script setup lang="ts">
  import {useAppLayout} from '@/common/composables/useAppLayout';

  const form = useForm({...});

  useAppLayout({
    title: 'Edit entry type',
    form,
    onSave: () => form.post('/admin/entry-types/save'),
  });
</script>
```

In a slideout this configures the panel; on a full page it configures the layout. `<LayoutSlot>`
teleports work the same way in both.

### Slots a slideout has no room for

`breadcrumbs`, `context-menu`, `title`, `title-badge`, `sidebar`, `subnav-actions` and `footer` are
still rendered as **hidden outlets** in a slideout, so a page written for a full page doesn't throw
or lose teleported content — that content is simply not shown.

### Pages that render `<AppLayout>` inline

Rendering `<AppLayout>` inside a slideout doesn't stack a second shell. The inner one becomes a
passthrough that forwards its props and `@save` up to the real shell, so `:title` and `:form` still
apply.

### Reading page props

`usePage()` always returns the **base** page. That's correct for shared props (flash, CSRF, the
`craft` bag), but a slideout reading `usePage().props.title` gets the title of the page *behind* it.

Page-specific data arrives as component props, so `defineProps` is the normal answer. If you need
the current screen's props generically:

```ts
import {useScreenPageProps} from '@/common/composables/screen';

const pageProps = useScreenPageProps(); // the slideout's own, or the base page's
```

## Stacking and nesting

One rule: **opening a slideout closes whatever is stacked above the thing that opened it.**

| Opened from | Result |
| --- | --- |
| The base page | Replaces any open panel |
| Inside panel 1 | Nests as panel 2 |
| Inside panel 1, while panel 2 is open | Replaces panel 2 |

This is resolved from the `opener` element's position in the DOM, so it needs no configuration —
double-clicking a second row on an index swaps the panel, while a link inside a panel nests below
it.

Closing a panel also closes anything nested inside it. Clicking the shade or pressing <kbd>Esc</kbd>
closes the **top** panel only, so each press peels one layer.

Stacked panels spread across the space beside the newest one, so the stack never runs off the edge
however deep it goes — each panel just peeks out a little less.

## Styling

| Custom property | Default | Notes |
| --- | --- | --- |
| `--slideout-width` | `55vw` | Must be a **length**, not a percentage — the stack offset subtracts it from `100vw`. |
| `--slideout-shade-color` | `rgb(0 0 0 / 40%)` | The dimming behind the panel. |

```css
:root {
  --slideout-width: 40rem;
}
```

Or per panel:

```ts
open(url, {width: '40rem'});
```

The shell lays itself out against the **panel's** width via a container query, not the viewport's —
the details column stacks below the content under `44rem` and sits beside it above. On screens
narrower than `640px` the panel becomes a full-width sheet.

## The element index

Double-clicking a row on the entries index opens that element in a slideout. It's delegated on the
element container, so both table and cards views get it.

Double-clicks on interactive elements — links, buttons, checkboxes, action menus, `craft-*` controls
— are ignored so those keep their own behaviour. Rows are skipped when the element isn't editable,
is trashed, or is inside an element picker.

The behaviour lives in `useElementQuickEdit` and reads its metadata from the element chip's
`data-cp-url` / `data-editable` / `data-trashed` attributes. Those come from
`ElementHtml::elementChipHtml()` — the generic `chipHtml()` does **not** emit them, so an index
rendering chips the other way won't be double-clickable.

## Coexisting with the legacy stack

The legacy `Craft.CpScreenSlideout` is untouched and still used by
`resources/js/common/components/SlideoutButton.vue`. Both stacks share `z-index: 100` and interleave
by DOM order.

Two things to know if you touch this area:

- **Don't reuse legacy class names.** The legacy stylesheet owns `.slideout-shade` and hides it with
  `:not(.visible) { display: none }`. The Vue shade is `.cp-slideout-shade` for that reason.
- The legacy payload's key order is pinned by `tests/Feature/Http/Responses/CpScreenSlideoutTest.php`.
  If that test goes red, the jQuery slideout stack is broken.

## Not supported yet

- **Element drafts.** Saving an entry from a slideout writes directly; drafts, autosave and
  provisional drafts need `Craft.ElementEditor`, which isn't wired in. See
  [Saving](#server-rendered-screens).
- **Deep linking.** Opening a slideout doesn't change the URL, and it can't be linked to.
- **Coordinated stacking with legacy slideouts.** A Vue panel opened over a
  `Craft.CpScreenSlideout` will compete with it for the shade and <kbd>Esc</kbd>.

## Files

| Path | What it is |
| --- | --- |
| `resources/js/common/slideouts/` | Store, request layer, host, panel, `useSlideout` |
| `resources/js/common/layouts/AppLayout.vue` | The shell dispatcher |
| `resources/js/common/layouts/screens/` | `PageScreen`, `SlideoutScreen`, `PassthroughScreen`, the shared contract |
| `resources/js/common/composables/screen.ts` | Screen context, shell key, props store |
| `resources/js/pages/cp/Screen.vue` | Fallback page for screens without `inertiaPage()` |
| `resources/js/common/slideouts/submitScreenForm.ts` | Saving for server-rendered screens |
| `resources/js/modules/settings/composables/useSettingsSave.ts` | Saving for Vue pages, both contexts |
| `resources/js/modules/elements/composables/useElementQuickEdit.ts` | Index double-click |
| `src/Http/Responses/CpScreenResponse.php` | The slideout branch and its two wire formats |
