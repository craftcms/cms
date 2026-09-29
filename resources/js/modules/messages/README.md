# Messages

> Usage docs, including how plugins send messages, are in
> [`docs/messages.md`](../../../../docs/messages.md). This README covers the module's internals.

The one path every control panel message ("Entry saved.", "Couldn’t save
entry.") takes to the screen. Whatever raises a message, it ends up in
`showMessage()`, which drops repeats, sends messages that name an inline outlet
to that outlet, and shows everything else in the default display. This layer
doesn't care how a message looks; the display does.

Messages are short-lived feedback. Anything worth keeping belongs in the
activity timeline or the notification center, which are separate systems.

## Files

| File | Role |
| --- | --- |
| `messages.ts` | The queue: `showMessage()`, dedupe, inline outlets, the `Craft.cp.display*()` shim |
| `index.ts` | Barrel, plus `installMessages()` (drains `window.CraftMessageQueue`, listens for `craft-message`). `legacy.ts` calls this on Twig pages |
| `components/cp-messages.ts` | `<cp-messages id="messages">`, the container both the Twig layout and `app.blade.php` render. Builds its heading and live regions, and holds the corner in its `position` attribute. Defined by importing the barrel |
| `inertia.ts` | `setUpInertiaMessages()`, called by `bootstrap/cp.ts`: installs the queue, shows each page response's messages, and keeps the user's position/duration preferences current. Kept out of the barrel so the Twig bundle doesn't load Inertia's router |
| `useMessages.ts` | `useMessages()` and `useMessageOutlet()` for Vue |

## Where messages come from

| Source | How it reaches `showMessage()` |
| --- | --- |
| `Flash::success()` / `error()` / `notice()` on a redirect | The `messages` Inertia prop (read in `inertia.ts`), or `window.CraftMessageQueue` from `_layouts/components/messages.twig` on Twig pages |
| `asSuccess()` / `asFailure()` JSON responses | `showMessagesFromResponse(data)`, which reads the response's `messages`, or legacy `Craft.cp.displaySuccess(data.message, data.notificationSettings)` |
| Vue code | `useMessages().success(text)` |
| Legacy JS and plugins | `Craft.cp.displayNotification()` / `displayNotice()` / `displaySuccess()` / `displayError()`, which the shim routes through `showMessage()` |
| Code that can't import this module | `window.dispatchEvent(new CustomEvent('craft-message', {detail}))` (`runAction()` uses this) |

## Rules

- **Shown once.** A message with an `id` (every server message has one) is
  remembered in `sessionStorage`, so an Inertia history visit or a reload
  doesn't replay it. JSON responses put the id in `notificationSettings` too, so
  legacy callers dedupe without changes. A message identical to one still on
  screen isn't shown again.
- **Stored for the next page only when there is one.** The server flashes
  messages on redirects; JSON responses return them in the body instead.
  Messages shown just before the page unloads are carried over to the next
  page.
- **Inline or default.** A message with a `target` goes to the inline outlet
  registered under that name (`<InlineFlash target="…">`, or
  `useMessageOutlet()`), or goes to the default display when the outlet isn't
  on the page. Send `messageTargetHeaders(name)` with a request to target the
  messages the server flashes while handling it.

## The default display and accessibility

The default display is still the legacy `Craft.CP.Notification` in
`packages/craftcms-legacy/cp/src/js/CP.js`, which keeps its name because
plugins use it. Callers keep the instance `display*()` returns, and the
copied-elements card subclasses it, so porting the renderer is deferred.

- `<cp-messages>` holds two live regions that exist before any message:
  `role="alert"` for errors and `role="status"` for everything else.
- Modelled on Sonner: the newest sits nearest the screen edge with up to four
  more stacked behind it (`Craft.CP.Notification.visibleCount`, 5 in all).
  The stack spreads out while it's hovered, pressed or holds focus; the
  "Skip to messages" skip link (shown only while there are messages) moves
  focus to it and <kbd>Esc</kbd> hands focus back. Messages beyond the visible
  count are hidden and `inert`.
- Messages close themselves after the user's `notificationDuration`
  preference, except errors and ones with actions in their details, which stay
  until dismissed. Timers pause while the stack is expanded, pressed, or the
  page is hidden. Swiping one toward the screen edge dismisses it.
- With Laravel Debugbar on, bottom-corner stacks sit above its header row,
  using the same `--cp-debug-bar-height` as the sidebar and page shell.
- No slide animation under `prefers-reduced-motion`.

`InlineFlash` renders the same pair of regions for its own messages.
