# MCP in Craft 5

Craft exposes content and configuration through the Model Context Protocol. It uses the official `mcp/sdk` package and Craft’s shared [OAuth service](oauth.md).

## Servers

- **Authenticated HTTP:** `{cpTrigger}/mcp`, normally `/admin/mcp`. Users sign in through Craft and authorize the client through OAuth. They need both **Access the control panel** and **Use Craft MCP** permissions. Craft checks the user's current account state and permissions for each request.
- **Public HTTP:** a configurable site route, `/mcp` by default. It is disabled until an administrator enables it and approves capabilities and content sources under **Settings → MCP**.
- **Stdio:** `php craft mcp/serve --user=…`. The argument accepts a user ID, username, or email. That user must be active and have **Use Craft MCP** permission. The process acts as that user.

HTTP requests use the stateless transport for protocol version `2026-07-28`. Send JSON-RPC messages with POST. Opening an HTTP endpoint in a browser shows its URL and setup instructions for common AI assistants; other GET requests receive a `405` response. Stdio uses the SDK's session-based transport. It omits capabilities marked `RequiresHttp`; restart the process after changing permissions, instruction configuration, or the content model externally.

## Authenticated HTTP setup

Update Craft and run its database migrations, then generate signing keys:

```sh
php craft oauth/generate-keys
```

Grant the intended users **Use Craft MCP** and control panel access. Configure an MCP client with the authenticated endpoint URL, normally `https://example.com/admin/mcp`. Use the normal Craft login process, including two-factor authentication, then approve or deny the client's consent request.

MCP uses the OAuth resource ID `mcp`. Clients discover registration, authorization, and token endpoints through OAuth metadata. See the [OAuth guide](oauth.md) for signing keys, shared configuration, discovery URLs, token lifetimes, and consent behavior.

Each token carries one of three access levels. A broader scope includes the narrower ones:

| Scope | Grants |
| --- | --- |
| `mcp:read` | Resources, prompts, and tools with `readOnlyHint: true`. |
| `mcp:write` | Also tools without `readOnlyHint: true`. |
| `mcp:admin` | Also capabilities marked `RequiresAdmin` or `RequiresAdminChanges`, including reads. |

The user chooses the access level on the consent screen. It defaults to read only, and administrators are the only users offered full access. When a client authorizes again while the user still has an active token for it, Craft skips the consent screen and keeps the level the user chose before. Clients can send `prompt=consent` to show the screen anyway. An unauthenticated request receives a `401` response whose `WWW-Authenticate` header requests every MCP scope. Scopes only narrow access: the user's Craft permissions still apply. The stdio server is not scoped.

## Configuration

Create `config/mcp.php` to configure the transport:

```php
<?php

return [
    'instructions' => 'Use the editorial style guide when changing article content.',
    'filters' => [],
];
```

`filters` contains Yii action-filter definitions applied to authenticated MCP requests. `instructions` adds site-specific guidance to the instructions returned by `info.get`.

Signing keys, token lifetimes, and client redirect restrictions are configured separately in [`config/oauth.php`](oauth.md#configuration).

## Public content

Under **Settings → MCP**, choose a public route, enable the server, and approve each tool, resource, resource template, or prompt to expose. A capability must be marked `PublicMcp` before it is eligible for approval.

Approve the sites, sections, nested-entry fields, volumes, user groups, and other element types the public content tools may read. These approvals are stored in project config under `mcp`. Drafts, revisions, and inactive content each require a separate approval. An empty source list exposes none of that source's content.

Public callers have no Craft user identity. They receive only approved capabilities. The core public tools `craft-context-get` and `craft-query` describe and query approved content. Public queries default to 25 results and are capped at 100.

## Capabilities and permissions

Authenticated and stdio clients can inspect and manage sites, sections, entry types, fields, field layouts, entries, drafts, revisions, structures, assets, volumes, users, addresses, user groups, permissions, routes, image transforms, plugins, and project config. They can also search content and run content-model checks.

Clients can also perform these maintenance tasks:

- **Caches and queue:** `caches.list`, `caches.clear`, `queue.status`, `queue.run`, `queue.retry-all`, and `queue.release-all` do what the Caches and Queue Manager utilities do. They require access to the utility, and are unavailable when it is listed in `disabledUtilities`. `queue.run` runs waiting jobs for up to `maxSeconds` (30 by default, 120 at most) and requires `craft\queue\Queue`. The other queue tools require a queue that implements `craft\queue\QueueInterface`.
- **System status:** `system-status.get` reads whether the system is live. `system-status.enable` and `system-status.disable` change it for administrators when `allowAdminChanges` is enabled. They refuse when `isSystemLive` is set in the general config, when the project config stores an environment variable reference for the status, or when the project config is read-only.
- **Project config:** `project-config.rebuild` rebuilds the project config from the database, as the Project Config utility does. It requires an administrator and refuses while the project config is read-only.
- **Preferences:** `preferences.get` and `preferences.update` read and change the authenticated user's preferred language and formatting locale.

Field settings and project config values whose keys look sensitive, as defined by the `security` component's `sensitiveKeywords`, are returned redacted as `•` characters. This includes environment variable references. Project config diffs are redacted the same way. To keep a saved value when updating field settings, send its redacted value back.

Asset creation and replacement accept HTTPS file references. Craft rejects local paths, private network addresses, redirects, and files that exceed the configured upload limits.

Capability discovery applies user permissions, `allowAdminChanges`, and the access token's scope. Record operations also enforce the corresponding element permissions. Unavailable capabilities are omitted from discovery and cannot be called directly.

Start with `info.get`. Read `elements.schema` or `craft://element-types/{type}` before changing elements, and `elements.field-schema` before writing custom fields. `configuration.schema` describes each configuration operation. List results supply pagination information; retain the query criteria and use the returned offsets to continue.

## Plugin extensions

Register SDK-attributed capability classes with an event handler during plugin initialization:

```php
use craft\events\RegisterComponentTypesEvent;
use craft\services\Mcp;
use yii\base\Event;

Event::on(Mcp::class, Mcp::EVENT_REGISTER_CAPABILITY_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = MyCapabilities::class;
});
```

For example:

```php
<?php

use craft\mcp\attributes\RequiresPermission;
use Mcp\Capability\Attribute\McpTool;

class MyCapabilities
{
    #[McpTool(name: 'my-plugin.site-name', description: 'Returns the site system name.')]
    #[RequiresPermission('accessCp')]
    public function siteName(): string
    {
        return Craft::$app->getSystemName();
    }
}
```

The SDK attributes `McpTool`, `McpResource`, `McpResourceTemplate`, and `McpPrompt` identify capabilities. Craft collects plugin classes on first capability discovery; it does not scan plugin directories. Duplicate capability identities are rejected.

Craft provides method attributes `RequiresPermission`, `RequiresAdmin`, `RequiresAdminChanges`, `RequiresHttp`, and `PublicMcp`. `RequiresAdmin` and `RequiresAdminChanges` also require the `mcp:admin` scope. Set `readOnlyHint` on reads; tools without `readOnlyHint: true` require the `mcp:write` scope. `PublicMcp` makes a method eligible for public approval. Plugin public methods must enforce their own content restrictions; approvals for Craft's core public content tools do not automatically filter arbitrary plugin code.

Additional extension points are:

- `Craft::$app->get('mcp')->getElementAdapterRegistry()->register(MyAdapter::class)` for adapters implementing `craft\mcp\elements\ElementAdapter`.
- `Craft::$app->get('mcp')->getCheckRegistry()->register(MyCheck::class)` for content-model checks implementing `craft\mcp\contentmodel\Check`.
- `craft\mcp\serializers\ElementSerializer::EVENT_SERIALIZING`, with `craft\mcp\events\ElementSerializing`, to change serialized data. Check the event's `public` flag before adding fields.
- `craft\base\InputSchemaFieldInterface` on custom field types to describe the values they accept with `getInputSchema()`.
- `craft\mcp\AdminInstructions::EVENT_COLLECT_INSTRUCTIONS`, with `craft\mcp\events\CollectingAdminInstructions`, to append plugin guidance to `info.get`.
