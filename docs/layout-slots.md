# Layout slots

A control panel screen is drawn by a shell (`PageScreen`, or `SlideoutScreen` in a slideout) with
named regions — the title, the actions beside it, the sidebar, the details pane, the footer. Any
component rendered inside a screen can fill one of those regions with `<LayoutSlot>`, even though
its own markup lives in the main content area. The content is teleported into the region but stays
in the component's scope, so its bindings remain reactive.

Plugins get the component globally as `craft:layout-slot`, the screen layout itself as
`craft:app-layout`, `craft:screen-panel` for [details panels](#details-panels), and
`craft:cp-container`, which pads page content to line up with the shell's header and toolbar:

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

`craft:layout-slot`, `craft:screen-panel` and `craft:app-layout` only render on a page your plugin
registered with `Cp.$inertia.register()`. Craft checks the nearest Inertia page above the
component, so this includes anything that page renders, but not your components rendered on
someone else's page: a dashboard widget, a UI node, an element details panel. There, they render nothing and log a warning
in development builds. This keeps a plugin from replacing the chrome of a page it doesn't own.

`craft:cp-container` only lays out content and works anywhere.

## Regions

### Full page

```
+----------------------------------------------------------------------------------------------------------------------+
| Crumb / Crumb / Crumb    [context-menu]                                                                              |
+----------+-----------------------------------------------------------------------------------------------------------+
| CP nav   | page-header                                                                                               |
|          +-------------------------+-------------------------------------------------------+-------------------------+
|          | content-sidebar         | error-summary                                         | content-details         |
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

`page-header` is the panel's first row, spanning the secondary nav, the content and the details
pane, and only appears once it's filled. The details pane only appears once `content-details` is filled or the screen has
[details panels](#details-panels); the sidebar shows the secondary nav
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

`context-menu`, `content-toolbar`, `title`, `content-sidebar`, `subnav-actions`, `page-header` and
`page-footer` have no place in a slideout. Their content is kept but not shown.

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
| `content-details`         | Opens the details pane. See below.        |
| `content-footer`          |                                           |
| `additional-buttons`      | In the footer, beside the Save menu.      |
| `primary-action`          | The Save button; its menu stays.          |
| `page-header`             | Atop the panel. Hidden until filled.      |
| `page-footer`             |                                           |

See [slideouts](slideouts.md) for how a page renders in a slideout.

Region names are public API. An unknown name logs a warning in development builds.

## Details panels

The details pane can hold panels: a rail of icon buttons beside it, each opening one panel, as the
element editor's Info, Activity and Revisions do. Add one with `craft:screen-panel`. It's in the
rail while the component is mounted, and its content is teleported into the panel, staying in the
component's scope like a `LayoutSlot`'s:

```vue
<script setup lang="ts">
  import {resolveComponent} from 'vue';

  const ScreenPanel = resolveComponent('craft:screen-panel');
</script>

<template>
  <ScreenPanel id="sync-log" label="Sync log" icon="list" :order="10">
    <SyncLog />

    <template #header-actions>
      <craft-button size="small" variant="plain">Clear</craft-button>
    </template>
  </ScreenPanel>
</template>
```

| Prop            | Notes                                                                                   |
| --------------- | --------------------------------------------------------------------------------------- |
| `id`            | Unique among the screen's panels. On a full page, it's the URL hash of the open one.    |
| `label`, `icon` | The panel's heading, and its button in the rail.                                        |
| `initialIcon`   | Shows the label's first letter instead of the icon.                                     |
| `order`         | Where it goes in the rail; lower comes first.                                           |
| `status`        | `{label, indicator}`, shown in the panel's header.                                      |
| `scrollsItself` | The content scrolls itself, so the panel's body doesn't.                                |
| `initialFocus`  | A function returning what gets focus when the panel is opened. Defaults to its heading. |

The `header-actions` slot goes in the panel's header, before its close button.

A screen opens on its first panel, or on the one in the URL hash. Each full page and each slideout
has panels of its own; a page's panels don't show in a slideout opened over it. The shell can add
panels of its own that stay from page to page. A page still opens on its own first panel, unless
the person left one of those open.

Content in `content-details` and panels can go together. While there are no panels, the content
fills the details pane as it always has. Once a panel is added, by the page or the shell, the
content becomes a panel of its own, labelled "Details", and is rendered again there.

To add a panel to the element editor, which isn't your page, register it with
`Cp.$elementDetailsPanels.register()` instead.

## Example

The workbench registers a "Layout Slots" nav item (`workbench/layout-slots`) and a "Layout Slots
Demo" dashboard widget, built with public API only:

- `workbench/resources/js/layout-slots-demo/LayoutSlotsDemo.vue` — a plugin page that fills
  regions, with a few more to toggle, including details panels.
- `workbench/resources/js/layout-slots-demo/LayoutSlotsDemoWidget.vue` — a widget on the core
  Dashboard whose slot is refused.
- `workbench/resources/js/layout-slots-demo/register.ts` — registration through `Cp.booting()`.
