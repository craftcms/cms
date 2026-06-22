# Server-Driven Menu Actions

Menu items in Craft's control panel can trigger client-side behavior using a closed set of actions. This lets you drive
UI behavior — HTTP requests, clipboard writes, file downloads, and custom events — without writing client-side
JavaScript.

---

## Action Primitives

Each menu item button has a single `action` property containing one of the primitive types:

| Type | What it does |
|---|---|
| `clipboard` | Writes a string to the clipboard |
| `http` | Fires a fetch request (GET/POST/PATCH/DELETE) |
| `event` | Dispatches a `CustomEvent` on `window` |
| `download` | Triggers a file download |

### Primitive shapes

```typescript
type BaseAction =
  | { type: 'clipboard'; value: string }
  | { type: 'http'; method?: 'GET' | 'POST' | 'PATCH' | 'DELETE'; url: string; body?: Record<string, unknown>; confirm?: string }
  | { type: 'event'; name: string; detail?: Record<string, unknown>; confirm?: string }
  | { type: 'download'; url: string; filename?: string }
```

The `confirm` field on `http` and `event` primitives shows a native browser confirmation dialog before the action executes.

---

## Defining Actions in PHP

### On elements

Override `safeActionMenuItems()` or `destructiveActionMenuItems()` on your element class. Items returned from `destructiveActionMenuItems()` automatically receive `'destructive' => true` and are grouped at the bottom of the menu.

```php
protected function safeActionMenuItems(): array
{
    return [
        ...parent::safeActionMenuItems(),
        [
            'icon'  => 'clipboard',
            'label' => t('Copy handle'),
            'action' => [
                'type'  => 'clipboard',
                'value' => $this->handle,
            ],
            'feedback' => [
                'success' => ['message' => t('Copied!')],
                'error'   => ['message' => t('Copy failed')],
            ],
        ],
    ];
}

protected function destructiveActionMenuItems(): array
{
    return [
        ...parent::destructiveActionMenuItems(),
        [
            'icon'   => 'trash',
            'label'  => t('Delete'),
            'action' => [
                'type'    => 'http',
                'method'  => 'DELETE',
                'url'     => UrlHelper::actionUrl('my-plugin/things/delete', ['id' => $this->id]),
                'confirm' => t('Are you sure you want to delete this?'),
            ],
            'feedback' => [
                'success' => ['message' => t('Deleted')],
                'error'   => ['message' => t('Could not delete')],
            ],
        ],
    ];
}
```

### Menu item properties

| Property      | Type     | Description                                                                                        |
|---------------|----------|----------------------------------------------------------------------------------------------------|
| `label`       | `string` | Required. The item's visible label.                                                                |
| `icon`        | `string` | Optional icon name.                                                                                |
| `action`      | `array`  | A `BaseAction` descriptor (see above). Omit for link-type items.                                   |
| `url`         | `string` | Makes the item a link instead of a button.                                                         |
| `feedback`    | `array`  | Success/error feedback config (see below).                                                         |
| `disabled`    | `bool`   | Disables the item.                                                                                 |
| `destructive` | `bool`   | Styles item as destructive (danger color). Auto-set for items from `destructiveActionMenuItems()`. |
| `variant`     | `string` | Color variant: `'danger'`, `'success'`, etc.                                                       |

### Feedback config

```php
'feedback' => [
    'success' => ['message' => 'Saved!'],
    'error'   => ['message' => 'Save failed'],
],
```

Feedback messages temporarily replace the item label. The item resets to idle after `feedbackDuration` (default 1000ms).

> **Note:** For `http` actions, a loading spinner is shown on the icon while the request is in-flight. Other primitives
> are synchronous and skip straight to success/error.

---

## For Plugin Authors

### Listening to the `DefineActionMenuItems` event

Plugins can append items to any element's action menu without subclassing the element:

```php
use CraftCms\Cms\Element\Events\DefineActionMenuItems;
use CraftCms\Cms\Entry\Entry;
use Illuminate\Support\Facades\Event;

Event::listen(DefineActionMenuItems::class, function (DefineActionMenuItems $event) {
    if (!$event->element instanceof Entry) {
        return;
    }

    $event->items[] = [
        'icon'  => 'arrow-up-right-from-square',
        'label' => 'Open in My Plugin',
        'action' => [
            'type'   => 'event',
            'name'   => 'my-plugin:open-editor',
            'detail' => ['elementId' => $event->element->id],
        ],
        'feedback' => [
            'success' => ['message' => 'Opened'],
        ],
    ];
});
```

### Using `event` as the JS escape hatch

The `event` primitive is the intended extension point for plugins that need real client-side behavior. Ship a `window.addEventListener` listener in your plugin's asset bundle and fire it from the descriptor.

**PHP (descriptor):**

```php
'action' => [
    'type'   => 'event',
    'name'   => 'my-plugin:open-editor',
    'detail' => ['elementId' => $this->id],
],
```

**JavaScript (from your plugin):**

```javascript
window.addEventListener('my-plugin:open-editor', (e) => {
    MyPlugin.openEditor(e.detail.elementId);
});
```

There is no client-side action registry. Adding a new primitive type requires a change to Craft core — the `event` primitive covers all plugin-specific JS needs.

---

## Using `craft-action-item` directly

If you're rendering a menu item manually in a Vue or Inertia page, pass `action` and `feedback` as element properties:

```html
<craft-action-item
  icon="clipboard"
  .action=${{ type: 'clipboard', value: entry.handle }}
.feedback=${{ success: { message: 'Copied!' } }}
>
  Copy handle
</craft-action-item>
```

The `feedbackDuration` attribute (milliseconds, default `1000`) controls how long success/error messages show before the
item resets to idle.
