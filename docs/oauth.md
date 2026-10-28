# OAuth in Craft 5

Craft’s `Craft::$app->getOAuth()` service lets clients access APIs on behalf of Craft users. It uses `league/oauth2-server` directly. [MCP](mcp.md) uses this service, and plugins and custom modules can register their own protected APIs.

Each API is an OAuth **resource** with its own ID, URL, scopes, and required user permissions. Clients, authorization codes, access tokens, and refresh tokens are bound to that resource. A credential issued for one resource cannot authenticate against another, even if both use the same scope IDs.

## Setup

Run Craft’s database migrations, then generate signing keys:

```sh
php craft oauth/generate-keys
```

This writes `storage/oauth-private.key` and `storage/oauth-public.key` with restrictive permissions. Keep the private key outside the web root. Deploy the same keys to every application node, or configure shared keys below. Incoming requests do not generate keys.

`php craft oauth/generate-keys --force` replaces the generated keys and invalidates access tokens signed with the previous key. Refresh tokens use encryption derived from Craft’s `securityKey`; replacing only signing keys does not revoke them. If you configure keys elsewhere, update those keys separately.

## Configuration

Create `config/oauth.php`:

```php
<?php

return [
    'privateKey' => '$OAUTH_PRIVATE_KEY',
    'publicKey' => '$OAUTH_PUBLIC_KEY',
    'accessTokenDuration' => 'PT1H',
    'refreshTokenDuration' => 'P30D',
    'authorizationCodeDuration' => 'PT10M',
    'redirectDomains' => ['https://client.example', 'http://localhost'],
    'customSchemes' => [],
    'resources' => [],
];
```

`privateKey` and `publicKey` accept PEM contents or file paths, including Craft aliases and environment variable references. Omit them to use the generated files under `storage`. Durations use PHP `DateInterval` syntax. The defaults are one hour for access tokens, 30 days for refresh tokens, and ten minutes for authorization codes.

`redirectDomains` defaults to `['*']`, permitting HTTP and HTTPS registration redirects. An explicit list restricts registration to matching origins and optional path prefixes. HTTP loopback origins allow dynamic ports for local clients. `customSchemes` permits additional redirect schemes; those URIs must still have a host. Registered redirect URIs must match exactly during authorization and token exchange.

## Registering a plugin resource

Register a class-level event handler during your plugin or module’s `init()`, before OAuth resources are first requested:

```php
use craft\events\RegisterOAuthResourcesEvent;
use craft\helpers\UrlHelper;
use craft\services\OAuth;
use yii\base\Event;

Event::on(OAuth::class, OAuth::EVENT_REGISTER_RESOURCES, function(RegisterOAuthResourcesEvent $event) {
    $event->resources['my-plugin-api'] = [
        'scopes' => [
            'content:read' => 'Read content through My Plugin.',
            'content:write' => 'Create and change content through My Plugin.',
        ],
        'defaultScopes' => ['content:read'],
        'permissions' => ['myPlugin:useApi'],
        'resourceUrl' => fn() => UrlHelper::siteUrl('api/my-plugin'),
        'consentDescription' => 'This client would like to access My Plugin’s API as you.',
    ];
});
```

The event’s `resources` map accepts configuration arrays or `craft\oauth\Resource` objects. If you supply an object, its `id` must match the map key. Resource definitions can also be supplied in `config/oauth.php` under `resources`, using the same map structure. Event handlers receive those configured resources along with the built-in `mcp` resource. Avoid overwriting another plugin’s resource ID.

| Property | Meaning |
| --- | --- |
| Resource ID | Map key containing 1–64 letters, numbers, underscores, or hyphens. Use a stable plugin-specific ID. |
| `scopes` | Nonempty map of allowed scope IDs to human-readable consent descriptions. Scope IDs cannot contain spaces, quotes, backslashes, or non-ASCII characters. |
| `defaultScopes` | Allowed scopes to use when an authorization request omits `scope`. Defaults to an empty list. |
| `scopeLevels` | Optional list of scopes that form access levels, ordered from narrowest to broadest. See [Access levels](#access-levels). |
| `scopeLabels` | Optional map of access levels to short labels for the consent screen. |
| `userScopes` | Optional closure, `function(User $user, array $scopes): array`, that returns the scopes a user may be granted out of those given. |
| `permissions` | Craft permissions the user must currently have to authorize and use the resource. Every listed permission is required. |
| `resourceUrl` | Canonical absolute HTTP or HTTPS URL, supplied as a string or a closure with no arguments. Strings support environment variable references. |
| `consentDescription` | Explanation shown above the requested scopes on the consent screen. |

Every resource needs a distinct URL path, including when resources use different origins. URLs cannot contain credentials or fragments. Registering a resource does not create the API route or controller. In a multisite installation, generate the URL for a specific site or supply a fixed URL so the resource identity does not vary with the current site.

Register custom Craft permissions during plugin initialization as well:

```php
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use yii\base\Event;

Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
    $event->permissions[] = [
        'heading' => 'My Plugin',
        'permissions' => [
            'myPlugin:useApi' => ['label' => 'Use My Plugin’s API'],
        ],
    ];
});
```

Grant that permission to the intended users or groups. Include `accessCp` in the resource’s `permissions` if it requires control panel access. Resources without that requirement use Craft’s configured site login page during authorization. That page must implement Craft’s normal login flow and return the user to the authorization request.

## Protecting a controller

Register your API route through Craft’s normal site URL rules. For a plugin with handle `my-plugin` and an `ApiController`:

```php
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
    $event->rules['api/my-plugin'] = 'my-plugin/api/index';
});
```

Pass a PSR-7 request to `authenticate()`. It returns the authenticated Craft user or throws `OAuthServerException`. Its optional `$scopes` argument receives the scopes the token grants. For example, this read endpoint authenticates exclusively with a bearer token:

```php
namespace myvendor\myplugin\controllers;

use Craft;
use craft\web\Controller;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class ApiController extends Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionIndex(): Response
    {
        if (!$this->request->getIsGet()) {
            throw new MethodNotAllowedHttpException('GET request required.');
        }

        $oauth = Craft::$app->getOAuth();

        try {
            $user = $oauth->authenticate(
                ServerRequest::fromGlobals(),
                'my-plugin-api',
                ['content:read'],
            );
        } catch (OAuthServerException $exception) {
            $this->response->setStatusCode(401);
            $this->response->getHeaders()->set(
                'WWW-Authenticate',
                'Bearer resource_metadata="' . $oauth->resourceMetadataUrl('my-plugin-api') . '"',
            );

            return $this->asJson(['error' => 'invalid_token']);
        }

        return $this->asJson(['userId' => $user->id]);
    }
}
```

`allowAnonymous` allows requests without a Craft session to reach the action. The action still requires OAuth authentication before returning protected data. `authenticate()` checks the token’s signature, expiry, revocation, resource binding, allowed scopes, current account state, and resource permissions. It requires every scope in its third argument. Omitting that argument accepts any valid grant for the resource without requiring an operation-specific scope.

The returned user is not automatically installed as Craft’s session identity. Use that user when checking record and operation permissions. If existing code reads the current Craft identity, set it explicitly after authentication, following the session-free pattern in `McpController`.

A scope describes what the client may do; it does not grant additional Craft permissions to the user. Require `content:write` for write actions, then enforce the user’s permissions for the affected records and operations. A read token must not reach a write action just because the underlying user can edit content.

For write endpoints that authenticate exclusively with bearer tokens, disable CSRF validation only for those actions and require the appropriate HTTP method. Keep CSRF validation for actions that accept cookie-based authentication.

## Discovery and client flow

Craft provides resource-specific registration, authorization, consent, and token endpoints under the control panel URL. For `my-plugin-api` and the default `admin` control panel trigger:

| Endpoint | Method | Purpose |
| --- | --- | --- |
| `/admin/oauth/my-plugin-api/register` | POST | Register a public client with a JSON body containing `redirect_uris` and an optional `client_name`. |
| `/admin/oauth/my-plugin-api/authorize` | GET | Begin an authorization code request with S256 PKCE. |
| `/admin/oauth/my-plugin-api/approve` | POST | Complete Craft’s consent form with approval. |
| `/admin/oauth/my-plugin-api/deny` | POST | Complete Craft’s consent form with denial. |
| `/admin/oauth/my-plugin-api/token` | POST | Exchange an authorization code or refresh token using form-encoded parameters. |

Use `$oauth->url('register', 'my-plugin-api')` and the corresponding endpoint name to generate URLs with Craft’s routing configuration. Clients should obtain these URLs from discovery metadata. Approve and deny are used by Craft’s consent screen and require the logged-in session, CSRF token, and pending authorization token.

For an API at `https://example.com/api/my-plugin`, with the issuer at `https://example.com/admin/oauth/my-plugin-api`, discovery URLs are:

```text
https://example.com/.well-known/oauth-protected-resource/api/my-plugin
https://example.com/.well-known/oauth-authorization-server/admin/oauth/my-plugin-api
```

For MCP at `https://example.com/admin/mcp`, they are:

```text
https://example.com/.well-known/oauth-protected-resource/admin/mcp
https://example.com/.well-known/oauth-authorization-server/admin/oauth/mcp
```

Craft serves these routes automatically. The full resource or issuer path appears after the well-known prefix, including installation subdirectories. The web server must forward these origin-root paths to Craft when it runs under a subdirectory. If an API uses another origin, serve the URL returned by `$oauth->resourceMetadataUrl('my-plugin-api')` on that origin, forwarding to Craft as needed. `$oauth->protectedResourceMetadata('my-plugin-api')` returns the metadata array for a custom response handler.

The supported flow is:

1. Discover the resource and authorization server metadata.
2. Register a public client against that resource’s registration endpoint. The response supplies its `client_id`; clients use `token_endpoint_auth_method=none`.
3. Send the user to the authorization endpoint with `response_type=code`, `client_id`, the registered `redirect_uri`, `state`, space-separated `scope`, and an S256 `code_challenge` with `code_challenge_method=S256`.
4. Craft signs the user in and asks them to approve the requested scopes. The client validates the returned `state`.
5. Exchange the code at the token endpoint with `grant_type=authorization_code`, `client_id`, `redirect_uri`, `code`, and the original `code_verifier`.
6. Send the access token in `Authorization: Bearer …` when calling the API. Refresh it at the same token endpoint with `grant_type=refresh_token`, `client_id`, and `refresh_token`.

Only public clients, the authorization code grant with S256 PKCE, and refresh tokens are supported. Registering a client does not grant access. A registration request may include a supported scope subset, but the registration response advertises all allowed resource scopes; the authorization request determines the actual grant.

Discovery metadata, registration, and the token endpoint stay available while the system is offline, so clients can refresh tokens. Authorization still requires a user who may access the control panel while the system is offline, and each resource decides whether its API answers then. MCP requires the token's user to have that permission.

## Scopes, consent, and token lifetime

Authorization grants only the requested scopes, and the consent screen shows their descriptions. `defaultScopes` is used when `scope` is omitted. If no defaults are defined, the client must explicitly request at least one allowed scope. Scope IDs are local to a resource.

Consent is reused while the same user and client have unexpired, unrevoked access tokens covering the requested scopes. Adding a scope requires consent if existing grants do not cover it. `prompt=consent` asks again, `prompt=login` asks for a fresh login, and `prompt=none` returns an OAuth error when login or consent would be needed.

Refreshing rotates both tokens and revokes the previous access token and refresh token. A refresh without `scope` preserves the grant. A refresh may request fewer scopes but cannot add scopes. Removing an allowed scope from a resource invalidates credentials that contain it. Losing a required Craft permission, or becoming inactive, suspended, or locked, prevents the user from using the resource.

### Access levels

Scopes listed in `scopeLevels` are ordered access levels, and each level includes the ones before it. When a request includes levels, the consent screen asks the user to choose one of the requested levels they may be granted, preselecting the narrowest. The grant contains the chosen level and the narrower requested levels. Other requested scopes are listed and granted as usual. MCP uses `mcp:read`, `mcp:write`, and `mcp:admin`; see [MCP](mcp.md#authenticated-http-setup).

Consent is reused when the requested levels overlap the levels granted by the broadest level the user chose before. Craft then grants that earlier level, so a client that requests every level keeps the level the user chose. To require a level, check that the token holds it or a broader one.

`userScopes` limits the levels offered to each user, and the scopes granted when a token is issued or refreshed. For example, MCP offers `mcp:admin` only to administrators, and refreshing an `mcp:admin` token after the user loses admin status drops that scope.

## GraphQL

When the GraphQL API is enabled, Craft registers a `graphql` resource. Its URL is the primary site’s `actions/graphql/api` endpoint, so discovery metadata is served at `/.well-known/oauth-protected-resource/actions/graphql/api`. If your GraphQL API is routed elsewhere, change the resource’s `resourceUrl` from an `EVENT_REGISTER_RESOURCES` handler.

`graphql:read` and `graphql:write` are access levels. A read token can only run queries; a write token can also run mutations. Any active user may authorize a client, so resources don’t require control panel access.

Users can also create personal access tokens from **My Account** → **Tokens** in the control panel, for apps and scripts that can’t complete an OAuth authorization flow. Creating one requires an elevated session. Users choose a name, the scopes to grant, and an expiration (7, 30, or 90 days, or a year), and the token is shown once. Choosing an access level grants the narrower levels too. Each personal token gets its own client without redirect URIs, so it can’t be used to authorize anything else. The same screen lists the apps the user has authorized, and revoking one revokes its access and refresh tokens.

The screen lists every resource that sets `personalTokens` to `true`, which the user may access and which has scopes they may be granted. The `graphql` resource does, so it’s only offered when the GraphQL API is enabled; the `mcp` resource doesn’t, because MCP clients authorize themselves. If no resource offers personal tokens to the user, the screen isn’t shown. Plugins can offer their own resources by setting `personalTokens` (and a `label`) on the resource they register, and can manage tokens with `craft\\services\\OAuth::createPersonalAccessToken()`, `getPersonalTokenResources()`, `getUserGrants()`, and `revokeUserGrant()`.

Send the access token in `Authorization: Bearer …` to the GraphQL API. Instead of a schema token, Craft builds a schema from the GraphQL schema components that support authenticated requests and are available to the user, and sets the user as the request’s identity. Expired or invalid access tokens return HTTP 401 with a `WWW-Authenticate` header that points to the discovery metadata. GraphQL schema tokens keep working as before.

Schema components opt in with an `authenticated` key: `true` to make them available to every authenticated user, or a callable that receives the user and returns whether it’s available to them. Craft’s built-in components check the corresponding user permissions. For example, `sections.<uid>:read` requires `viewEntries:<uid>`, and `volumes.<uid>:save` requires `saveAssets:<uid>`. Categories, tags, and global sets are deprecated, so their components aren’t available to authenticated requests. Components are responsible for limiting their own results to what `craft\models\GqlSchema::$user` may access:

- Entry and asset queries, including related entries and assets, use the `editable` query param, so peer entries, drafts, and assets are hidden unless the user can view them. Root entry queries don’t return nested entries; those are available through their owners.
- Drafts, revisions, and non-enabled elements are available to users with control panel access. Entries’ `drafts`, `revisions`, `currentRevision`, `parent`, `ancestors`, `children`, and `descendants` fields only return entries the user can view, and drafts created with the `createDraft` mutation are always created by the authenticated user.
- Mutations check `craft\services\Elements::canSave()`, `canDelete()`, and `canCreateDrafts()` for the user before changing an element, and existing elements are checked before and after they’re populated.
- Results for authenticated requests are cached per user, and vary by the user’s current permissions. Each user’s schema is cached as well, and is rebuilt when the project config or their permissions change. Components whose `authenticated` callbacks depend on anything else should stay available and limit their results instead.
