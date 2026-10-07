# MCP in Craft 5

Craft exposes content and configuration through the Model Context Protocol. It uses the official `mcp/sdk` package and `league/oauth2-server` directly.

## Servers

- **Authenticated HTTP:** `{cpTrigger}/mcp`, normally `/admin/mcp`. Users sign in through Craft and authorize the client through OAuth. They need both **Access the control panel** and **Use Craft MCP** permissions. Craft checks the user's current account state and permissions for each request.
- **Public HTTP:** a configurable site route, `/mcp` by default. It is disabled until an administrator enables it and approves capabilities and content sources under **Settings → MCP**.
- **Stdio:** `php craft mcp/serve --user=…`. The argument accepts a user ID, username, or email. That user must be active and have **Use Craft MCP** permission. The process acts as that user.

HTTP requests use the stateless transport for protocol version `2026-07-28`. Send JSON-RPC messages with POST. Stdio uses the SDK's session-based transport. It omits capabilities marked `RequiresHttp`; restart the process after changing permissions, instruction configuration, or the content model externally.

## Authenticated HTTP setup

Update Craft and run its database migrations, then generate signing keys:

```sh
php craft mcp/generate-keys
```

This writes `storage/oauth-private.key` and `storage/oauth-public.key` with restrictive permissions. Keep the private key outside the web root. Deploy the same keys to every application node, or configure shared keys as shown below. Keys are generated explicitly, never by an incoming HTTP request.

Grant the intended users **Use Craft MCP** and control panel access. Configure an MCP client with the authenticated endpoint URL. Use the normal Craft login process, including two-factor authentication, then approve or deny the client's consent request.

OAuth supports discovery metadata, dynamic registration of public clients, the authorization code grant with S256 PKCE, and rotating refresh tokens. The scope is `mcp:use`. Default lifetimes are one hour for access tokens, 30 days for refresh tokens, and ten minutes for authorization codes. Refreshing revokes the previous access token and refresh token.

Consent is reused while the same client has an unexpired access token covering the requested scopes. `prompt=consent` requests consent again. `prompt=login` requests a fresh login. `prompt=none` returns an OAuth error when login or consent would be needed.

For an endpoint at `https://example.com/admin/mcp`, discovery URLs are:

```text
https://example.com/.well-known/oauth-protected-resource/admin/mcp
https://example.com/.well-known/oauth-authorization-server/admin/mcp
```

The endpoint's full path appears after the well-known prefix, including any installation subdirectory. The web server must forward these origin-root paths to Craft when Craft runs under a subdirectory. Discovery metadata supplies the registration, authorization, and token URLs; clients should use those URLs.

`php craft mcp/generate-keys --force` replaces existing signing keys and invalidates existing access tokens. Existing refresh tokens remain encrypted with a key derived from Craft's `securityKey`; replacing only the signing keys does not revoke refresh tokens.

## Configuration

Create `config/mcp.php` to configure the transport and OAuth server:

```php
<?php

return [
    'endpoint' => 'mcp',
    'instructions' => 'Use the editorial style guide when changing article content.',
    'privateKey' => '$MCP_PRIVATE_KEY',
    'publicKey' => '$MCP_PUBLIC_KEY',
    'accessTokenDuration' => 'PT1H',
    'refreshTokenDuration' => 'P30D',
    'authorizationCodeDuration' => 'PT10M',
    'redirectDomains' => ['https://client.example', 'http://localhost'],
    'customSchemes' => [],
    'filters' => [],
];
```

`privateKey` and `publicKey` accept PEM contents or file paths, including Craft aliases. If omitted, Craft uses the generated files under `storage`. The duration values use PHP `DateInterval` syntax.

`redirectDomains` defaults to `['*']`, which permits HTTP and HTTPS registration redirects. Set an explicit list to restrict clients to those origins and optional path prefixes. An HTTP loopback origin permits dynamic ports for local clients. `customSchemes` explicitly permits additional redirect schemes. Registered redirect URIs must match exactly during authorization and token exchange.

`filters` contains Yii action-filter definitions applied to authenticated MCP requests. `instructions` adds site-specific guidance to the instructions returned by `info.get`.

## Public content

Under **Settings → MCP**, choose a public route, enable the server, and approve each tool, resource, resource template, or prompt to expose. A capability must be marked `PublicMcp` before it is eligible for approval.

Approve the sites, sections, nested-entry fields, volumes, user groups, and other element types the public content tools may read. These approvals are stored in project config under `mcp`. Drafts, revisions, and inactive content each require a separate approval. An empty source list exposes none of that source's content.

Public callers have no Craft user identity. They receive only approved capabilities. The core public tools `craft-context-get` and `craft-query` describe and query approved content. Public queries default to 25 results and are capped at 100.

## Capabilities and permissions

Authenticated and stdio clients can inspect and manage sites, sections, entry types, fields, field layouts, entries, drafts, revisions, structures, assets, volumes, users, addresses, user groups, permissions, routes, image transforms, plugins, and project config. They can also search content and run content-model checks.

Asset creation and replacement accept HTTPS file references. Craft rejects local paths, private network addresses, redirects, and files that exceed the configured upload limits.

Capability discovery applies user permissions and `allowAdminChanges`. Record operations also enforce the corresponding element permissions. Unavailable capabilities are omitted from discovery and cannot be called directly.

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

Craft provides method attributes `RequiresPermission`, `RequiresAdmin`, `RequiresAdminChanges`, `RequiresHttp`, and `PublicMcp`. `PublicMcp` makes a method eligible for public approval. Plugin public methods must enforce their own content restrictions; approvals for Craft's core public content tools do not automatically filter arbitrary plugin code.

Additional extension points are:

- `Craft::$app->get('mcp')->getElementAdapterRegistry()->register(MyAdapter::class)` for adapters implementing `craft\mcp\elements\ElementAdapter`.
- `Craft::$app->get('mcp')->getCheckRegistry()->register(MyCheck::class)` for content-model checks implementing `craft\mcp\contentmodel\Check`.
- `craft\mcp\serializers\ElementSerializer::EVENT_SERIALIZING`, with `craft\mcp\events\ElementSerializing`, to change serialized data. Check the event's `public` flag before adding fields.
- `craft\mcp\contracts\ProvidesInputSchema` on custom fields to describe their writable input with `getMcpInputSchema()`.
- `craft\mcp\AdminInstructions::EVENT_COLLECT_INSTRUCTIONS`, with `craft\mcp\events\CollectingAdminInstructions`, to append plugin guidance to `info.get`.
