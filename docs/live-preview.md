# Live Preview

Live Preview shows the element editor beside the element's front end, which
reloads as edits are autosaved. Each full-page element editor has one; plugins
reach it through the `@craftcms/cp` import-map module:

```ts
import {
  getActivePreview,
  onPreviewEvent,
  refreshPreviews,
} from '@craftcms/cp';

// After changing something the previewed page shows, outside its editor:
refreshPreviews();

// Every preview on the page, including ones added later:
const stop = onPreviewEvent('afterUpdateIframe', ({target, iframe}, preview) => {
  console.log(`Loading ${target.label}`, iframe);
});
```

Leave `vue` and `@craftcms/cp` out of the plugin's bundle so it shares the
control panel's copies, and with them the page's list of previews.

Code that can't import `@craftcms/cp`, such as the legacy bundles, can
dispatch a `craft:refresh-previews` event on `window` to do the same as
`refreshPreviews()`.

## API

| Export | What it does |
| --- | --- |
| `getActivePreview()` | The open preview, or `null`. |
| `getPreviews()` | Every preview on the page, open or not. |
| `refreshPreviews()` | Reloads each open preview whose target auto-refreshes. |
| `onPreviewEvent(event, listener)` | Listens to an event from every preview. The listener gets the payload and the preview. Returns a function that stops listening. |
| `watchPreviews(callback)` | Calls `callback` for each preview, now and as they're added. Whatever it returns runs when that preview goes away. |

A preview (`LivePreviewController`) has reactive state (`isOpen`, `targets`,
`activeIndex`, `activeTarget`, `device`, `orientation`, `iframe`, `editor`),
methods (`open()`, `close()`, `refresh({resetScroll, reload})`,
`switchTarget(index)`), and `on(event, listener)` for its own events.
`refresh()` reloads when the target auto-refreshes; `reload: true` forces it,
and `reload: false` keeps the current frame.

### Events

| Event | When | Payload |
| --- | --- | --- |
| `beforeOpen` | Opening starts | – |
| `open` | The preview is shown | – |
| `beforeClose` | Closing starts | – |
| `close` | The preview is hidden | – |
| `switchTarget` | Another target was chosen | `{target}` |
| `beforeUpdateIframe` | Each time the frame might reload | `{target, resetScroll, refresh}` |
| `afterUpdateIframe` | A new frame was added, before it loads | `{target, iframe}` |
| `afterSaveDraft` | The editor autosaved a draft | `{data}` (the save's response) |
| `drag` | The divider moved | – |

## Standalone preview

`preview/{id}` opens an element straight into Live Preview, with nothing to
close it back to. Pass `siteId`, `draftId` or `revisionId` to choose what's
shown, and a signed `returnUrl` for where saving leaves for (the element's
post-edit URL by default).

## `Craft.Preview`

With the Yii2 adapter, Craft 5's `Craft.Preview` is a shim over the above:

- One instance mirrors each preview on the page. `Craft.Preview.getActive()`,
  `Craft.Preview.instances` and `Craft.Preview.refresh()` work as before;
  `refresh()` also refreshes classic `Craft.LivePreview` instances.
- Events keep Craft 5's names and payloads: `previewTarget` for `target`,
  `$iframe` (jQuery) for `iframe`, `response.data` for `data`. `open` and
  `close` are followed by `slideIn` and `slideOut`. Listeners added with
  `.on()` or `Garnish.on(Craft.Preview, …)` are called.
- `isActive`, `isVisible`, `activeTarget`, `url`, `$iframe`, `$editorContainer`,
  `editorWidth`, `editorWidthInPx`, `currentDeviceType` and `deviceOrientation`
  are read from the preview. `open()`, `close()`,
  `updateIframe(resetScroll, refresh)`, `switchTarget()`, `switchDeviceType()`
  and `switchOrientation()` drive it.
- `elementEditor` is `null`: the editor is Vue now.
- `new Craft.Preview(settings)` no longer builds a preview. It warns, and
  `open()` goes to the element's standalone preview page.

Classic (pre-3.2) `Craft.LivePreview` also ships with the adapter. Without the
adapter, neither global exists.
