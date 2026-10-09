# Layout slots

A control panel screen is drawn by a shell (`PageScreen`, or `SlideoutScreen` in a slideout) with
named regions — the title, the actions beside it, the sidebar, the details pane, the footer. Any
component rendered inside a screen can fill one of those regions with `<LayoutSlot>`, even though
its own markup lives in the main content area. The content is teleported into the region but stays
in the component's scope, so its bindings remain reactive.

Plugins get the component globally as `craft:layout-slot`, the screen layout itself as
`craft:app-layout`, and `craft:cp-container`, which pads page content to line up with the shell's
header and toolbar:

```vue
<script setup lang="ts">
  import {resolveComponent} from 'vue';

  const LayoutSlot = resolveComponent('craft:layout-slot');
  const CpContainer = resolveComponent('craft:cp-container');
</script>

<template>
  <LayoutSlot name="content-actions">
    <craft-button variant="primary">Sync now</craft-button>
  </LayoutSlot>

  <CpContainer>
    <p>Main content…</p>
  </CpContainer>
</template>
```

Filling a region replaces it: whatever the shell would show there — the page title, the secondary
nav, the error summary — is hidden.

## Only on your own pages

`craft:layout-slot` and `craft:app-layout` only render on a page your plugin registered with
`Cp.$inertia.register()`. Craft checks the nearest Inertia page above the component, so this
includes anything that page renders, but not your components rendered on someone else's page: a
dashboard widget, a UI node, an element details tab. There, they render nothing and log a warning
in development builds. This keeps a plugin from replacing the chrome of a page it doesn't own.

`craft:cp-container` only lays out content and works anywhere.

## Regions

### Full page

```
+----------------------------------------------------------------------------------------------------------------------+
| Crumb / Crumb / Crumb    [context-menu]                                                                              |
+----------+-------------------------+-------------------------------------------------------+-------------------------+
| CP nav   | content-sidebar         | error-summary                                         | content-details         |
|          |                         | +- content-toolbar ---------------------------------+ |                         |
|          |   (secondary nav)       | | content-toolbar-meta    content-toolbar-actions   | |                         |
|          |                         | +---------------------------------------------------+ |                         |
|          | +---------------------+ |                                                       |                         |
|          | | subnav-actions      | | title                     content-actions             |                         |
|          | +---------------------+ |                                                       |                         |
|          |                         | content-tabs                                          |                         |
|          |                         |                                                       |                         |
|          |                         |                                                       |                         |
|          |                         |      (page content)                                   |                         |
|          |                         |                                                       |                         |
|          |                         |                                                       |                         |
|          |                         | +- sticky footer -----------------------------------+ |                         |
|          |                         | | content-notices                                   | |                         |
|          |                         | | [primary-action | v]   additional-buttons         | |                         |
|          |                         | | content-footer                                    | |                         |
|          |                         | +---------------------------------------------------+ |                         |
|          +-------------------------+-------------------------------------------------------+-------------------------+
|          | page-footer                                                                                               |
+----------+-----------------------------------------------------------------------------------------------------------+
```

The details pane only appears once `content-details` is filled; the sidebar shows the secondary nav
unless `content-sidebar` replaces it, which also removes `subnav-actions`. The toolbar row and
sticky footer appear once something in them is filled.

### Slideout

```
+----------------------------------------------------------------------------------------------------------------------+
| Title    content-toolbar-meta                                                                                        |
|          content-toolbar-actions                                     content-actions                                 |
+----------------------------------------------------------------------------------------------------------------------+
| content-tabs                                                                                                         |
+-------------------------------------------------------------------------------------+--------------------------------+
| content-notices                                                                     | content-details                |
| error-summary                                                                       |                                |
|                                                                                     |                                |
|                                                                                     |                                |
|      (page content)                                                                 |                                |
|                                                                                     |                                |
|                                                                                     |                                |
| content-footer                                                                      |                                |
+-------------------------------------------------------------------------------------+--------------------------------+
| additional-buttons                                                             [Cancel]   [primary-action | v]       |
+----------------------------------------------------------------------------------------------------------------------+
```

`context-menu`, `content-toolbar`, `title`, `content-sidebar`, `subnav-actions` and `page-footer`
have no place in a slideout. Their content is kept but not shown.

The Save menu (`[primary-action | v]`) is the same in both: the primary action, which is a Save
button by default, then a menu of the `defaultFormActions` (Save and continue editing, unless the
page changes them) and the page's `formActions`, with `formAdditionalButtons` and
`formAdditionalActions` after it. A slideout without a Vue form gets the primary action alone.

To replace the primary action, fill the `primary-action` slot, or set it on the response. The
response's button always submits the screen's form:

```php
return new CpScreenResponse()
    ->primaryAction(Button::make()->label(t('Apply'))->variant(ButtonVariant::Fill));
```

```vue
<LayoutSlot name="primary-action">
  <craft-button type="submit" variant="fill">Apply</craft-button>
</LayoutSlot>
```

The slot wins over the response's button.

### All regions

| Name                      | Notes                                     |
| ------------------------- | ----------------------------------------- |
| `title`                   | Replaces the page `<h1>`.                 |
| `content-actions`         | Beside the title.                         |
| `context-menu`            | Beside the breadcrumbs.                   |
| `content-sidebar`         | Replaces the secondary nav.               |
| `subnav-actions`          | In the secondary nav.                     |
| `content-toolbar`         | Frames `content-toolbar-meta`/`-actions`. |
| `content-toolbar-meta`    |                                           |
| `content-toolbar-actions` |                                           |
| `content-tabs`            |                                           |
| `error-summary`           | Replaces the form's error summary.        |
| `content-notices`         | The sticky notices bar.                   |
| `content-details`         | Opens the details pane.                   |
| `content-footer`          |                                           |
| `additional-buttons`      | In the footer, beside the Save menu.      |
| `primary-action`          | The Save button; its menu stays.          |
| `page-footer`             |                                           |

See [slideouts](slideouts.md) for how a page renders in a slideout.

Region names are public API. An unknown name logs a warning in development builds.

## Example

The workbench registers a "Layout Slots" nav item (`workbench/layout-slots`) and a "Layout Slots
Demo" dashboard widget, built with public API only:

- `workbench/resources/js/layout-slots-demo/LayoutSlotsDemo.vue` — a plugin page that fills
  regions, with a few more to toggle.
- `workbench/resources/js/layout-slots-demo/LayoutSlotsDemoWidget.vue` — a widget on the core
  Dashboard whose slot is refused.
- `workbench/resources/js/layout-slots-demo/register.ts` — registration through `Cp.booting()`.
