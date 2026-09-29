# Messages

Messages are the short-lived feedback the control panel shows after something happens: "Entry
saved.", "Couldn’t save entry.", "Draft applied." Every message, whether it comes from a PHP
controller, a JSON response, Vue code, legacy JavaScript, or a plugin, goes through **one queue**
and is drawn by **one display**, so it looks and behaves the same wherever it came from, and it's
never shown twice.

> Messages are ephemeral. Anything someone needs to find again belongs in the
> [activity timeline](activity-logging.md) or the notification center, which are separate systems.
> Saving an entry, for example, shows an "Entry saved." message *and* records the change in the
> entry's activity.

## Basic Usage

From a controller, use the `RespondsWithFlash` helpers:

```php
use CraftCms\Cms\Http\RespondsWithFlash;

class WidgetsController
{
    use RespondsWithFlash;

    public function save(Request $request): Response
    {
        if (! $this->widgets->save($widget)) {
            return $this->asModelFailure($widget, t('Couldn’t save widget.'));
        }

        return $this->asSuccess(t('Widget saved.'));
    }
}
```

From Vue:

```ts
import {useMessages} from '@/modules/messages/useMessages';

const messages = useMessages();

messages.success(t('Widget saved.'));
messages.error(t('Couldn’t save widget.'));
```

That's all most code needs. The rest of this page covers what happens in between, inline messages,
and the options for plugins.

## Anatomy of a Message

Every message has the same shape, on the server and in the browser:

| Key | Type | Description |
| --- | --- | --- |
| `id` | `string` | A UUID. Server messages always have one; the browser uses it to never show a message twice. |
| `type` | `'notice' \| 'success' \| 'error'` | What the message means. Errors are announced assertively and stay until dismissed. |
| `message` | `string` | The text. Plain text, not HTML. |
| `settings` | `array` / `object` | Display settings: `icon`, `iconLabel`, `details` (HTML shown below the message), `persist`. Each type has a default icon and label. |
| `target` | `string \| null` | The [inline outlet](#inline-messages) the message belongs to. `null` means the default display. |

In TypeScript this is `CpMessage`, from `@/modules/messages`.

## How a Message Travels

```
Flash::success() / error() / notice()   →  session  →  Inertia `messages` prop ─┐
                                                    →  Twig CraftMessageQueue ───┤
asSuccess() / asFailure() (JSON)        →  response body `messages` ─────────────┤
useMessages() (Vue)                     ─────────────────────────────────────────┼─→ showMessage()
Craft.cp.display*() (legacy, plugins)   ── shim ─────────────────────────────────┤
window `craft-message` event            ─────────────────────────────────────────┘
```

`showMessage()` drops any message it has already shown, hands a message with a `target` to that
inline outlet, and sends everything else to the default display.

**A message is stored for the next page only when there is a next page.** Redirects flash it to the
session; JSON responses return it in the body and store nothing. This keeps a message from turning
up later on an unrelated page.

## Sending Messages from PHP

### From a controller

`CraftCms\Cms\Http\RespondsWithFlash` picks the right path for the request:

| Method | Redirect request | JSON request |
| --- | --- | --- |
| `asSuccess($message, $data, $redirect, $notificationSettings)` | Flashes a `success` message, then redirects. | Returns `message` in the body, plus `messages` and `notificationSettings` on control panel requests. Nothing is flashed. |
| `asFailure($message, $data)` | Flashes an `error` message and goes back. Validation errors in `$data['errors']` go to the error bag as well. | Returns a 400 with `message`, plus `messages` on control panel requests. |
| `asModelSuccess($model, …)` / `asModelFailure($model, …)` | Same as above, with the model's data and errors. | Same as above. |

```php
return $this->asSuccess(t('Settings saved.'));

// With display settings
return $this->asSuccess(t('Entry saved.'), notificationSettings: ['icon' => 'check']);

// A failure with field errors shows the field errors *and* the summary message
return $this->asModelFailure($entry, t('Couldn’t save entry.'));
```

A control panel JSON response looks like this:

```json
{
  "message": "Settings saved.",
  "messages": [
    {"id": "9b1f…", "type": "success", "message": "Settings saved.", "settings": {"icon": "check", "iconLabel": "Success"}, "target": null}
  ],
  "notificationSettings": {"icon": "check", "iconLabel": "Success", "id": "9b1f…"}
}
```

`message` and `notificationSettings` are there for older JavaScript that calls
`Craft.cp.displaySuccess(data.message, data.notificationSettings)`. The id rides along in the
settings, so that call and the new path never show the message twice.

### With `Flash` directly

When you're building the response yourself, use `CraftCms\Cms\Support\Flash`:

```php
use CraftCms\Cms\Support\Flash;

Flash::success(t('Site created'));
Flash::notice(t('Draft applied.'), ['icon' => 'draft']);
Flash::error(t('Couldn’t apply new migrations.'));

return to_route('craft.cp.settings.sites.index');
```

Each method takes `(?string $default, array $settings = [], ?string $target = null)` and returns the
message it flashed (or `null`). The first argument is a *default*: a signed `successMessage` or
`failMessage` request param (or a plain `notice` param, for `notice()`) takes precedence, which is
how front-end forms customise the text.

On control panel requests the message is added to the `cp-messages` session list with an id and the
type's default settings. On site requests it's written to Laravel's plain `notice`, `success` or
`error` session key, which is what front-end templates read.

Don't flash a message and then return JSON or throw a validation exception from a JSON request.
There's no next page to show it on, so it would appear on whatever page loads next.

| Method | Description |
| --- | --- |
| `Flash::success()`, `error()`, `notice()` | Flash a message for the next page. |
| `Flash::make($type, $message, $settings, $target)` | Build a message array (with a new id) without flashing it, e.g. to return in JSON. |
| `Flash::push($message)` | Flash a message array built with `make()`. |
| `Flash::all()` | Every message waiting to be shown on this request, including any flashed straight to the plain session keys. |
| `Flash::getSuccess()`, `getError()`, `getNotice()` | The latest message text of that type, for templates. |

### Plain session keys

`back()->with('success', …)`, `->with('error', …)` and `->with('notice', …)` still work on control
panel routes. `Flash::all()` picks them up and wraps them as messages, so they appear on both
Inertia and Twig pages. Core code should use `Flash` instead, which keeps settings and targets.

### In Twig

The control panel layout renders the flashed messages for you. If you're building a custom
layout, `cpMessages()` returns the same list as `Flash::all()`.

## Sending Messages from JavaScript

### Vue

```ts
import {useMessages} from '@/modules/messages/useMessages';

const messages = useMessages();

messages.success(t('Job restarted.'));
messages.notice(t('Nothing to update.'));
messages.error(t('Failed to retry job.'));

// With settings
messages.success(t('Entry saved.'), {details: chipHtml});
```

`useMessages()` doesn't use any lifecycle hooks, so it's safe to call outside `setup()` too.

### JSON responses you handle yourself

If you post to an endpoint that uses `asSuccess()` and handle the response yourself, show what came
back before you navigate or reload:

```ts
import {showMessagesFromResponse} from '@/modules/messages';

const response = await axios.post(url, data);
showMessagesFromResponse(response.data);
router.reload();
```

`showMessagesFromResponse()` shows the response's `messages`, falls back to a plain `message`, and
returns whether it showed anything. The display lives outside the Vue app, so the message survives
the visit.

### Anywhere else

`showMessage()` takes a full message:

```ts
import {showMessage} from '@/modules/messages';

showMessage({type: 'success', message: t('Copied.')});
```

Code that can't import the module, such as web components in `@craftcms/ui`, can dispatch a
`craft-message` event on `window` instead. `runAction()` does this for JSON responses that carry
`messages`:

```ts
window.dispatchEvent(
  new CustomEvent('craft-message', {
    detail: {type: 'success', message: 'Copied.'},
  })
);
```

## Inline Messages

Some feedback belongs next to the control that caused it: "Email sent" beside a **Test** button,
say. A message with a `target` goes to the inline outlet registered under that name, and falls
back to the default display if no such outlet is on the page, so it's never lost.

Use inline messages sparingly, for feedback about one specific control. Page-level outcomes, like
saving a form, go to the default display.

### `<InlineFlash>`

`InlineFlash` is a ready-made outlet. Send the target with the request, and messages the server
flashes while handling it land in the outlet:

```vue
<script setup lang="ts">
  import InlineFlash from '@/common/components/InlineFlash.vue';
  import {messageTargetHeaders} from '@/modules/messages';

  function sendTest() {
    testForm.submit(test(), {headers: messageTargetHeaders('email-test')});
  }
</script>

<template>
  <craft-button @click="sendTest">{{ t('Test') }}</craft-button>
  <InlineFlash target="email-test" :busy="testForm.processing" />
</template>
```

| Prop | Type | Description |
| --- | --- | --- |
| `target` | `string` | The outlet's name. |
| `busy` | `boolean` | Clears the current message when it turns on, e.g. when the form is resubmitted. |

Errors stay until the next message or until `busy` turns on. Other messages clear after the user's
notification duration. Like the default display, `InlineFlash` renders a `role="alert"` and a
`role="status"` region up front, so screen readers announce what appears.

`messageTargetHeaders(name)` sets the `X-Craft-Message-Target` request header
(`Flash::TARGET_HEADER`). Any message flashed during that request gets that target unless it names
its own. A server can also target a message directly:

```php
Flash::error($message, target: 'login');
```

### Custom outlets

Register your own outlet with `useMessageOutlet()`. It's active for as long as the component is
mounted, and the most recently registered outlet for a name wins:

```ts
import {useMessageOutlet} from '@/modules/messages/useMessages';

const loginError = ref('');

useMessageOutlet('login', (message) => {
  loginError.value = message.message;
});
```

Outside Vue, `registerMessageOutlet(name, outlet)` does the same and returns an unregister
function.

## Plugins

Plugins can send messages however suits them. Everything below goes through the same queue and
display as core messages.

### PHP

Laravel-style controllers:

```php
use CraftCms\Cms\Support\Flash;

use function CraftCms\Cms\t;

Flash::success(t('Import complete.', category: 'my-plugin'));

// Or, in a controller using RespondsWithFlash
return $this->asSuccess(t('Import complete.', category: 'my-plugin'));
```

Craft 5-style code keeps working through the Yii adapter:

```php
Craft::$app->getSession()->setNotice(Craft::t('my-plugin', 'Import queued.'));

$this->setSuccessFlash(Craft::t('my-plugin', 'Import complete.'));
return $this->asSuccess(Craft::t('my-plugin', 'Import complete.'));
```

Plain Laravel session keys are picked up on control panel pages too:

```php
return back()->with('success', 'Import complete.');
```

### JavaScript

The `Craft.cp` API is unchanged:

```js
Craft.cp.displaySuccess(Craft.t('my-plugin', 'Import complete.'));
Craft.cp.displayNotice(Craft.t('my-plugin', 'Import queued.'));
Craft.cp.displayError(Craft.t('my-plugin', 'Import failed.'));

// Details with a link or button make the message stay until it's dismissed
Craft.cp.displayNotice(message, {details: '<a href="/admin/my-plugin/log">View log</a>'});
```

When you call an endpoint that uses `asSuccess()`, pass the returned settings along. They carry
the message's id, so it's never shown twice:

```js
const {data} = await Craft.sendActionRequest('POST', 'my-plugin/import/run');
Craft.cp.displaySuccess(data.message, data.notificationSettings);
```

For code that doesn't depend on `Craft.cp`, dispatch the event:

```js
window.dispatchEvent(
  new CustomEvent('craft-message', {
    detail: {type: 'success', message: 'Import complete.'},
  })
);
```

`Craft.cp.displayNotification()` still returns the `Craft.CP.Notification` instance, so existing
code that calls `.close()` or listens for `show` keeps working.

## The Default Display

The default display is the stack of messages in a corner of the screen. It's drawn by
`Craft.CP.Notification` (in `packages/craftcms-legacy/cp/src/js/CP.js`) into the `<cp-messages>`
element, and styled by `resources/css/messages.css`. It's modelled on
[Sonner](https://sonner.emilkowal.ski).

> The renderer is still the legacy class because code holds on to the instance `display*()`
> returns (to call `.close()` or listen for `show`), and the "Entry copied." card extends it.
> Porting it is deferred; the queue in front of it doesn't depend on how it draws.

### Stacking

The newest message sits nearest the screen edge, with up to four more stacked behind it. Older
messages are hidden and `inert` until newer ones close. The stack spreads out while it's hovered,
pressed, or holds keyboard focus.

### Timing

- Messages close themselves after the user's **Notification duration** preference.
- **Errors, and messages with a link, button or form field in their details, stay until dismissed.**
- Timers pause while the stack is spread out or pressed, and while the browser tab is hidden, then
  resume with the time they had left.
- `settings.persist` keeps any message until it's dismissed.

### Dismissing

Each message has a close button. A message can also be swiped toward the screen edge.
`Craft.cp.clearNotifications()` dismisses every message that has a visible close button.

### Accessibility

- `<cp-messages>` renders two live regions before any message arrives: `role="alert"` for errors
  and `role="status"` for everything else. It's a landmark labelled "Messages".
- A **Skip to messages** link appears in the skip links while there are messages. It moves focus
  to the stack, which spreads it out and pauses its timers. <kbd>Esc</kbd> returns focus to where
  it was.
- Focus never moves into a message on its own, except one whose details contain a button or form
  field.
- With `prefers-reduced-motion`, nothing animates.

### Position

The stack sits in the corner set by the user's **Notification position** preference
(`start-start`, `start-end`, `end-start` or `end-end`, block axis first), held in the
`<cp-messages>` element's `position` attribute. The corners are logical, so they mirror in
right-to-left languages. Changing the preference takes effect on the next Inertia response,
without a reload.

When Laravel Debugbar is on, bottom-corner stacks sit above its header row, using the same
`--cp-debug-bar-height` custom property (from `cp.css`) as the sidebar and page shell.

### Tuning

These CSS custom properties on `.cp-messages` control the layout:

| Property | Default | Description |
| --- | --- | --- |
| `--messages-gap` | `10px` | Space between messages when the stack is spread out. |
| `--messages-lift` | `10px` | How far each message behind the front one peeks out when collapsed. |
| `--messages-inset` | `24px` (`16px` below 600px) | Distance from the screen edges. |
| `--messages-duration` | `400ms` | Animation duration. |

And these static properties on `Craft.CP.Notification`:

| Property | Default | Description |
| --- | --- | --- |
| `visibleCount` | `5` | How many of the newest messages are shown. |
| `autoClose` | `true` | Whether messages close themselves at all. |

## Deduplication

A message is never shown twice:

- **By id.** Shown ids are remembered in `sessionStorage`, so an Inertia history visit, a reload, or
  a message that arrives by two routes (a JSON response and a following page load) is only shown
  once. Legacy calls dedupe the same way when their settings include the `id` from
  `notificationSettings`.
- **By content.** A message with the same type and text as one still on screen isn't stacked.

A message shown moments before the page unloads (a save that shows one and then navigates) is
carried over and shown again on the next page, so it isn't lost unread.

## Setup

The queue is installed once per page, before any message can arrive:

- **Inertia pages.** `bootstrap/cp.ts` calls `setUpInertiaMessages()` (from
  `@/modules/messages/inertia`) before the app mounts. It installs the queue, shows the `messages`
  prop from every page response (deferred a tick, so the new page's outlets have registered), and
  keeps `Craft.notificationPosition`, `Craft.notificationDuration` and the container's `position`
  in step with the user's preferences.
- **Twig pages.** `legacy.ts` calls `installMessages()` (from `@/modules/messages`), which shows
  anything the layout pushed onto `window.CraftMessageQueue` and replaces the array so later pushes
  show straight away.

Importing `@/modules/messages` also defines `<cp-messages>`. `inertia.ts` is deliberately not
exported from that barrel, so the Twig bundle doesn't pull in Inertia's router.

## Files

| File | Role |
| --- | --- |
| `src/Support/Flash.php` | Flashing messages and reading them back. |
| `src/Http/RespondsWithFlash.php` | `asSuccess()`, `asFailure()` and friends. |
| `resources/js/modules/messages/messages.ts` | The queue: `showMessage()`, dedupe, outlets, the `Craft.cp.display*()` shim. |
| `resources/js/modules/messages/index.ts` | Barrel, `installMessages()`, the `craft-message` listener, `messageTargetHeaders()`. |
| `resources/js/modules/messages/inertia.ts` | Shows each Inertia response's messages and keeps preferences current. |
| `resources/js/modules/messages/useMessages.ts` | `useMessages()` and `useMessageOutlet()`. |
| `resources/js/modules/messages/components/cp-messages.ts` | The `<cp-messages>` container. |
| `resources/js/common/components/InlineFlash.vue` | The inline outlet component. |
| `resources/css/messages.css` | The default display's styles. |
| `resources/templates/_layouts/components/messages.twig` | Renders the container and queues flashed messages on Twig pages. |
