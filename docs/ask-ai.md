# Ask AI

**Ask AI** is the control panel’s AI chat. It opens from the header bar in a panel beside the screen, and answers the user’s questions about the site’s content and settings. It looks things up with Craft’s [MCP tools](mcp.md), as the user, so it can only see and change what they can.

The chat runs as the `craft:assistant` [AI task](ai.md), which needs a model with tool calling, and it’s shown to users with the **Use the AI assistant** permission (`useAiAssistant`) while that task is available. Conversations are stored with `laravel/ai`’s conversation tables, one user each.

Plugins can extend the chat in three ways:

- **[Tools](#tools)**: MCP tools a plugin registers are offered to the chat too.
- **[Context](#context)**: things the user adds to the chat, such as an order, which go with their messages, and tools that work with them.
- **[Text expanders](#text-expanders)**: suggestions the message box offers as the user types a character, such as `@`.

## Tools

The chat gets the same tools as Craft’s stdio MCP server. A plugin that [adds MCP capabilities](mcp.md#adding-capabilities-from-a-plugin) adds them to the chat as well, with nothing else to register:

- The same edition, admin, and permission requirements apply. Tools marked with `#[RequiresHttp]` aren’t offered, and neither are tools marked with `#[PublicMcp]`, which only the public server has.
- Tool names are renamed for providers, which only allow letters, numbers, underscores, and hyphens: `reviews.list` becomes `reviews_list`. The chat shows the original name.
- The user must approve each call of a tool that can change anything, before it runs. Mark tools that only read with `readOnlyHint: true` so they run without asking. For tools that change things but can’t delete or overwrite them, set `destructiveHint: false`, which tells the user so in the approval request.
- Changes made through tools are recorded in activity as the AI assistant’s.

```php
#[McpTool(
    name: 'reviews.list',
    description: 'Lists reviews for an entry, newest first.',
    annotations: new ToolAnnotations(readOnlyHint: true),
)]
```

The model only sees a tool’s name, description, and schema. Describe what the tool returns and when to use it, as the model chooses tools by their descriptions.

## Context

Users can add things to the chat’s context, such as an element and some of its fields. Each item is shown as a chip above the message box, and goes with the user’s next message. Plugins can add their own kinds of items.

The element the user is editing isn’t a chip. Each request names it to the model, without its content, and the model reads it with the `read_editor_element` tool when a question is about it, including changes the user hasn’t saved.

An item has two halves:

- In the browser, the plugin adds the item with `Cp.$assistant.addContext()`, or offers it in the message box’s **Add** menu.
- On the server, a **context type** with the same handle describes the items to the model, and can give the model tools that work with them.

Items whose type isn’t registered on the server are left out of what the model sees.

### Adding items in the browser

Add an item from your plugin’s control panel script, once `Cp` is booting:

```ts
window.Cp.booting((cp) => {
  cp.$assistant.registerContextSource({
    id: 'commerce:order',
    label: () => 'Current order',
    icon: 'cart-shopping',
    order: 1,
    // Only while an order page is open, and the order isn’t in the context yet.
    available: () =>
      currentOrderId() !== null &&
      cp.$assistant.getContext(`commerce:order:${currentOrderId()}`) ===
        undefined,
    add: () => {
      const orderId = currentOrderId();

      cp.$assistant.addContext({
        type: 'commerce:order',
        key: `commerce:order:${orderId}`,
        label: `Order #${orderId}`,
        icon: 'cart-shopping',
        data: {orderId},
        reference: () => ({orderId}),
      });
    },
  });
});
```

`addContext()` takes:

| Property | Description |
| -------- | ----------- |
| `type` | Required. The handle of the context type on the server, such as `commerce:order`. |
| `label` | Required. Shown on the item’s chip. |
| `data` | Required. What the context type gets for the item with each message, as an object, or a function that returns it when the message is sent, for values that change, such as a form’s. |
| `key` | Identifies what the item is, so adding it again replaces it rather than adding it twice. Defaults to a new key each time. |
| `icon` | The chip’s icon. |
| `status` | A status the chip shows in place of its icon, as `{fill, label, draft?}`, such as an element’s. A value, or a reactive function. |
| `reference` | What the conversation keeps of the item for later messages. See [Earlier items](#earlier-items). |
| `meta` | Kept with the item, to read back with `getContext()`. Not sent. |

`addContext()` returns the item’s `ref`, which the server gets with it, and which `Cp.$assistant.removeContext(ref)` takes. `Cp.$assistant.getContext(key)` returns the item added with a key while it’s in the context. It’s reactive, so `available()` can use it to hide a source once its item is in.

`Cp.$assistant.open()` opens the chat and moves focus to its message box, for a button such as “Ask AI about this order”.

### Context sources

`registerContextSource()` adds an item to the message box’s **Add** menu:

| Property | Description |
| -------- | ----------- |
| `id` | Required. A plugin-scoped identifier, such as `commerce:order`. Registering another source with the same `id` throws. |
| `label` | Required. A function that returns the menu item’s label, read each time the menu opens. |
| `add` | Required. Adds the source’s items to the context with `addContext()`. |
| `icon` | The menu item’s icon. |
| `order` | Lower comes first. Craft’s own source, the element being edited, uses `0`. |
| `available` | Whether the source has anything to add now, read each time the menu opens. Defaults to always. The menu is hidden while no source is available. |

### Context types

On the server, implement `CraftCms\Cms\Ai\Contracts\AssistantContextTypeInterface`:

```php
<?php

declare(strict_types=1);

namespace Acme\Commerce\Ai;

use Acme\Commerce\Orders;
use CraftCms\Cms\Ai\Contracts\AssistantContextTypeInterface;
use CraftCms\Cms\Ai\Data\AssistantContext;
use Illuminate\Http\Request;

readonly class OrderContext implements AssistantContextTypeInterface
{
    public function __construct(private Orders $orders) {}

    public static function handle(): string
    {
        return 'commerce:order';
    }

    public function resolve(Request $request, array $items): ?AssistantContext
    {
        $orders = [];

        foreach ($items as $item) {
            $order = is_int($item['orderId'] ?? null) ? $this->orders->find($item['orderId']) : null;

            // Items come from the browser: leave out those the user can’t view.
            if ($order && $request->user()?->can('view', $order)) {
                $orders[] = $order;
            }
        }

        if ($orders === []) {
            return null;
        }

        return new AssistantContext(
            description: implode("\n", array_map(
                static fn ($order): string => "- Order #{$order->id} (ID {$order->id}), {$order->status}, placed {$order->dateOrdered->format('Y-m-d')}",
                $orders,
            )),
            tools: [new RefundOrder($orders)],
        );
    }
}
```

Register it from your plugin’s `boot()` method:

```php
use CraftCms\Cms\Ai\AssistantContextTypes;

public function boot(AssistantContextTypes $contextTypes): void
{
    $contextTypes->register(OrderContext::class);
}
```

- `handle()` must be globally unique. Prefix it with your plugin handle. It may only contain letters, numbers, underscores, hyphens, and colons, and must start with a letter. `craft:element` is Craft’s own.
- The context type is resolved from the container for each message, so constructor injection works.
- `resolve()` gets the items of its type in the message’s context, as `{type, ref, ...data}`, where `data` is what was passed to `addContext()`.
- The items come from the browser. Check that the user can view each one, and leave out the ones they can’t. Return `null` when there’s nothing to describe.

`AssistantContext` has two properties:

- `description` goes in the model’s instructions, in Markdown. Say what each item is, with the IDs the model needs to look it up with tools, and what the model can do with it. Keep it short: it’s sent with every request of the turn. Content in it is treated as data, not instructions, but don’t put text in it that you wouldn’t show the user.
- `tools` are `laravel/ai` tools, which are only offered while the items are in the context. Their names must be unique among the chat’s tools, so prefix them, and use only letters, numbers, underscores, and hyphens.

A context tool that changes anything should ask the user to approve each call, as Craft’s MCP tools do. Implement `Laravel\Ai\Contracts\Approvable` with the `Laravel\Ai\Concerns\InteractsWithApprovals` trait:

```php
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

use function CraftCms\Cms\t;

class RefundOrder implements Approvable, Tool
{
    use InteractsWithApprovals;

    // name(), description(), schema(), and handle() as for any tool…

    protected function needsApproval(Request $request): Approval|bool
    {
        return Approval::required(t('This refunds the order.', category: 'commerce'));
    }
}
```

Check permissions in the tool’s `handle()` as well. The model can call it with any arguments, not only the items’ IDs.

### Earlier items

Each message keeps the items it was sent with. With later messages, the context type gets them again, marked with `earlier: true`, so the model can keep working with an order the user added a few messages ago. In `$items`, they come after the latest message’s items. Items that are still in the context aren’t repeated.

Earlier items are `{type, ref, ...reference, earlier: true}`, where `reference` is what the item’s `reference` function returned when its message was sent. It’s stored with the message, so keep it to what identifies the item, such as an ID, and look the rest up again in `resolve()`. Items without a `reference` aren’t sent again. Each earlier item is only included once.

An earlier item may no longer exist, or the user may no longer be able to view it. Check it as you check the latest message’s items.

## Text expanders

The message box offers suggestions as the user types a trigger character, which can add what’s chosen to the context. Craft has two: `@` for elements the user can view, and `{` for the fields of the element being edited.

```ts
window.Cp.booting((cp) => {
  cp.$assistant.registerTextExpander({
    id: 'commerce:orders',
    trigger: '!',
    label: 'Orders',
    // The URL of your plugin’s action, such as one Wayfinder generates.
    source: () => OrderSuggestionsController.url(),
    limit: 10,
    select: (option) => {
      const {orderId} = option.data as {orderId: number};

      cp.$assistant.addContext({
        type: 'commerce:order',
        key: `commerce:order:${orderId}`,
        label: option.label,
        icon: 'cart-shopping',
        data: {orderId},
        reference: () => ({orderId}),
      });
    },
  });
});
```

| Property | Description |
| -------- | ----------- |
| `id` | Required. A plugin-scoped identifier. |
| `trigger` | Required. The character that opens the suggestions. Each trigger belongs to one expander: registering a taken `id` or `trigger` throws. |
| `label` | Required. Names the suggestions, for assistive technology. |
| `options` | A function that returns the suggestions, read as the user types. Return none to offer nothing. |
| `source` | A function that returns the URL of an action that returns the suggestions, in place of `options`. |
| `limit` | The most suggestions to show. |
| `boundary` | Where the trigger counts: `whitespace` (the default), after a space or at the start; `start`, only at the start; or `anywhere`. |
| `select` | Runs once a suggestion is inserted, such as to add it to the context. |

Each suggestion is `{label, value, keywords?, data?}`. `value` is inserted in the message, `keywords` also match what the user types, and `data` is yours. A short `data.hint`, such as what kind of thing the suggestion is, is shown beside it.

A `source` action gets `query`, with the trigger character and what the user typed after it, such as `!104`, and `limit`. It returns an array of suggestions as JSON. Check the user’s permissions in it as in any action.

## What isn’t extensible

The agent, its instructions, its own display tools (`show_elements`, `show_components`, `show_field`, `ask_user`, and `propose_changes`), and the chat’s HTTP API are internal, and can change.

To change what the chat can do for everyone, add MCP capabilities, which other MCP clients get too. To add something only the chat needs, such as tools that work with what the user has open, add a context type.
