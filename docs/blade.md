# Blade Templates

Craft 6 can render Laravel Blade templates through the same template lifecycle used by Twig page templates.

Blade support is outcome parity with Twig, not a clone of Twig syntax. Use native Blade and Laravel features where they already exist, and use Craft's Blade directives for Craft-specific template behavior such as page lifecycle placeholders, resource registration, template caches, hooks, namespacing, and response control.

Blade templates are trusted PHP-backed templates. They are compiled to PHP and are not sandboxed. Use sandboxed Twig rendering for untrusted template strings.

## Template Discovery

Front-end site templates can use `.blade.php` files. The default front-end template extension order is:

- `twig`
- `html`
- `blade.php`

That means `templates/news.twig` will be chosen before `templates/news.blade.php` when both exist. Change `defaultTemplateExtensions` if a project needs a different order:

```php
<?php

return [
    'defaultTemplateExtensions' => ['blade.php', 'twig', 'html'],
];
```

Craft's template resolver still owns template lookup before either engine renders anything. Public/private template filtering, path containment, localized site template lookup, index template lookup, and registered template roots all apply before Craft decides whether the resolved file should be rendered by Twig or Blade.

Control panel template lookup supports `twig`, `html`, and `blade.php`. Blade application and plugin views can also be rendered directly through Laravel's view system.

## Rendering Templates

The engine-neutral renderer resolves a Craft template name and then chooses Twig or Blade based on the resolved file extension.

```php
<?php

use CraftCms\Cms\Support\Facades\Template;

$html = Template::renderTemplate('articles/_entry', [
    'entry' => $entry,
]);

$pageHtml = Template::renderPageTemplate('articles/show', [
    'entry' => $entry,
]);
```

The global helpers use the same renderer:

```php
<?php

$html = template('articles/_entry', ['entry' => $entry]);
$pageHtml = pageTemplate('articles/show', ['entry' => $entry]);
```

Automatic selection uses the first registered renderer that supports the resolved file. Pass a `TemplateEngine` or custom renderer name to force a renderer:

```php
<?php

use CraftCms\Cms\View\TemplateEngine;

$html = template('articles/_entry', ['entry' => $entry], renderer: TemplateEngine::Blade);
```

`renderPageTemplate()` and `pageTemplate()` wrap Blade templates in Craft's page lifecycle, so queued head/body resources are rendered into the page placeholders.

If you already have a Laravel view name or file path, resolve the low-level `BladeRenderer` through the `Template` facade:

```php
<?php

use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\Support\Facades\Template;

$renderer = Template::renderer(TemplateEngine::Blade);

$partial = $renderer->renderTemplate('my-plugin::tokens.index', [
    'token' => $token,
], TemplateMode::Cp);

$inline = $renderer->renderString('Hello, {{ $name }}', [
    'name' => 'Craft',
], TemplateMode::Cp);
```

Slash-style view names and namespaced views both work:

```php
<?php

view('dashboard/_index', $variables);
view('my-plugin::tokens.index', $variables);
view('my-plugin::nested/screen', $variables);
```

Use `view()->file($path, $variables)` when you intentionally want to render a concrete file path.

## Renderers

`TemplateManager` is a scoped Laravel manager. Its built-in renderer names are `twig` and `blade`, represented by `TemplateEngine`. Custom renderers implement `TemplateRendererInterface`, including file support, resolved-template rendering, and inline-string rendering. A replacement for the built-in Twig renderer must implement `TwigRendererInterface`.

Register renderers once, typically from a service provider’s `boot()` method, through the `Template` facade. The registration is replayed whenever Laravel creates a new manager scope:

```php
<?php

use App\Templates\MarkdownRenderer;
use CraftCms\Cms\Support\Facades\Template;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Template::extend(
            'markdown',
            static fn (Container $container) => $container->make(MarkdownRenderer::class),
        );
    }
}
```

New renderer names are appended after the built-in renderers. Re-registering an existing name replaces its creator without changing its selection position. Like other Laravel managers, an already-resolved renderer remains cached in the current scope until `forgetRenderers()` is called. New manager scopes receive the latest creator automatically and resolve their own renderer instances.

Don’t wrap `extend()` in `callAfterResolving()`, because the manager registers its own scope replay. Renderer creators should resolve scoped dependencies from the supplied container rather than capturing request-specific instances.

## Routes

Dynamic template routes now render Blade templates through the shared renderer. Template routes still resolve the Craft template first, then render the resolved `.blade.php` file as a page template.

This means routed Blade site templates respect:

- public/private template filtering
- project-config and element route variables
- headless-mode 404 behavior for site requests
- Craft template mode restoration
- page lifecycle resource injection
- template rendering events

## Template Globals

Blade views receive the same Craft template globals as Twig through a Laravel view composer. Common globals include:

- `$craft`
- `$currentSite`
- `$currentUser`
- `$siteName`
- `$siteUrl`
- `$systemName`
- `$language`
- `$devMode`
- `$isInstalled`
- `$loginUrl`
- `$logoutUrl` (the action URL for a CSRF-protected POST logout form)
- `$setPasswordUrl`
- `$now`
- `$today`
- `$tomorrow`
- `$yesterday`

Plugins can customize the shared globals with `TemplateGlobalsResolving`:

```php
<?php

use CraftCms\Cms\View\Events\TemplateGlobalsResolving;
use Illuminate\Support\Facades\Event;

Event::listen(TemplateGlobalsResolving::class, function (TemplateGlobalsResolving $event) {
    $event->globals['myGlobal'] = 'value';
});
```

Blade also keeps Laravel's normal view data, helpers, components, stacks, composers, and escaping behavior.

## Page Lifecycle

Page Blade templates can mark where Craft should output registered resources:

```blade
<!doctype html>
<html>
    <head>
        <title>{{ $entry->title }}</title>
        @craftHead
    </head>
    <body>
        @craftBeginBody

        <main>
            {{ $entry->title }}
        </main>

        @craftEndBody
    </body>
</html>
```

Use these directives in full page templates:

| Directive | Output position |
|---|---|
| `@craftHead` | Registered head resources |
| `@craftBeginBody` | Registered body-begin resources |
| `@craftEndBody` | Registered body-end resources |

## Resource Directives

Craft's resource directives register assets and arbitrary HTML with `HtmlStack`.

```blade
@craftCss('body { color: red; }')
@craftJs('console.log("ready")')
@craftHtml('<div id="portal"></div>')
@craftScript('{"@context":"https://schema.org"}', \CraftCms\Cms\View\Enums\Position::Head, ['type' => 'application/ld+json'])
```

Each resource directive also supports block capture:

```blade
@craftCss
    body {
        color: red;
    }
@endCraftCss

@craftJs
    console.log('ready');
@endCraftJs

@craftHtml
    <div id="portal"></div>
@endCraftHtml

@craftScript
    {"name": "Craft"}
@endCraftScript
```

Directive signatures:

| Directive | Signature |
|---|---|
| `@craftCss` | `(string $css, array $options = [], ?string $key = null)` |
| `@craftJs` | `(string $js, array $options = [], ?string $key = null)` |
| `@craftHtml` | `(string $html, int\|Position $position = Position::BodyEnd, ?string $key = null)` |
| `@craftScript` | `(string $script, int\|Position $position = Position::BodyEnd, array $options = [], ?string $key = null)` |

`@craftCss` and `@craftJs` can register inline code or file URLs. `@craftJs` accepts a `position` option:

```blade
@craftJs('/assets/app.js', ['position' => \CraftCms\Cms\View\Enums\Position::BodyEnd->value])
```

## Template Cache Directives

Blade templates can use Craft's template cache service:

```blade
@craftCache('entry-'.$entry->id, duration: '+1 hour')
    {{ $entry->title }}
@endCraftCache
```

The first argument is the cache key. Without `global: true`, the key is scoped to the current site and request path. With `global: true`, it is scoped to the current site only.

```blade
@craftCache('navigation', global: true)
    @include('_navigation')
@endCraftCache
```

The directive supports these options:

| Option | Description |
|---|---|
| `global` | Store the fragment outside the current request path. |
| `duration` | A date string, such as `'+1 hour'`. |
| `expiration` | A concrete expiration value, such as a `DateTimeInterface` or Carbon instance. |
| `withResources` | Capture and replay registered resources with the cached body. Defaults to `true`. |
| `condition` | Cache only when this value is truthy. |
| `unless` | Bypass caching when this value is truthy. |

For Twig-style conditional cache blocks, use the Blade-specific aliases:

```blade
@craftCacheIf($entry->cacheable, 'entry-'.$entry->id, duration: '+1 hour')
    {{ $entry->title }}
@endCraftCache

@craftCacheUnless($isPreview, 'entry-'.$entry->id)
    {{ $entry->title }}
@endCraftCache
```

`@craftCacheIf($condition, ...$cacheArguments)` caches when `$condition` is true. `@craftCacheUnless($condition, ...$cacheArguments)` caches when `$condition` is false.

Preview requests and tokenized requests bypass template caches automatically.

## Hooks

Use `@craftHook` to invoke Craft template hooks from Blade:

```blade
@craftHook('cp.elements.toolbar')
```

The current Blade scope is passed to hook handlers by reference. If a hook mutates the context, the mutated values are extracted back into the Blade scope after the hook runs.

```blade
@php($label = 'Original')

@craftHook('my-plugin.label')

{{ $label }}
```

## Namespaces

Use `@craftNamespace` when rendering field inputs or other markup that needs Craft's input-name namespacing:

```blade
@craftNamespace('fields')
    <label for="title">Title</label>
    <input id="title" name="title">
@endCraftNamespace
```

This rewrites the captured HTML so the input is submitted under `fields[title]`, with matching ID and `for` attributes. Pass `true` as the second argument to also namespace classes:

```blade
@craftNamespace('fields', true)
    ...
@endCraftNamespace
```

## Pagination

Use `@craftPaginate` with a paginatable query. It assigns `$paginate` and `$paginatedItems` in the current Blade scope:

```blade
@craftPaginate($entries)

@foreach($paginatedItems as $entry)
    <article>{{ $entry->title }}</article>
@endforeach

@if($paginate->nextUrl)
    <a href="{{ $paginate->nextUrl }}">Next</a>
@endif
```

## Auth and Edition Requirements

Craft-specific access directives are available for template-level requirements:

```blade
@craftRequireLogin
@craftRequireGuest
@craftRequireAdmin
@craftRequireAdmin(false)
@craftRequirePermission('accessCp')
@craftRequireEdition('pro')
```

Use Laravel-native Blade directives for normal Laravel authorization flows where they already fit:

```blade
@auth
    ...
@endauth

@can('update', $entry)
    ...
@endcan
```

## Response Directives

Blade templates can control the response in the same way Craft templates can:

```blade
@craftRedirect('news', 302, notice: 'Redirected')
@craftHeader('X-Robots-Tag: noindex')
@craftExpires(1, 'hour')
@craftExit
@craftExit(404, 'Not found')
```

`@craftExit` without a status exits the template and returns whatever has already been rendered. With a status, it aborts with that HTTP response.

## Twig and Blade Interop

Blade can include any Twig or Blade view that Laravel's view finder can resolve. Craft registers Twig as a Laravel view engine, and registered application, plugin, and template roots are available to `@include()`.

This is Laravel view resolution, not a second call to Craft's template resolver. Use `template()` or `pageTemplate()` from PHP when you need Craft template-name resolution, public/private template checks, or page-template wrapping.

```blade
@include('_navigation')
@include('articles/_entry', ['entry' => $entry])
@include('my-plugin::tokens.index')
```

There is no separate `@craftTemplate` directive. Use Blade's native `@include`, `@extends`, components, and stacks where those are the natural Blade features.

Twig can render Laravel Blade views with the `blade()` function:

```twig
{{ blade('my-plugin::tokens.index', { token: token }) }}
{{ blade('my-plugin::nested/screen', { entry: entry }) }}
```

The `blade()` function returns safe HTML and uses Laravel view resolution.

## Rendering Events

Craft exposes engine-neutral rendering events for both Twig and Blade:

- `CraftCms\Cms\View\Events\TemplateRendering`
- `CraftCms\Cms\View\Events\TemplateRendered`
- `CraftCms\Cms\View\Events\PageTemplateRendering`
- `CraftCms\Cms\View\Events\PageTemplateRendered`

Before events run before template resolution, so listeners can mutate the template name, variables, or template mode before a renderer is selected. After events expose the final renderer name as a string:

```php
<?php

use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\TemplateEngine;
use Illuminate\Support\Facades\Event;

Event::listen(TemplateRendered::class, function (TemplateRendered $event) {
    if ($event->rendererName !== TemplateEngine::Blade->value) {
        return;
    }

    $event->output = trim($event->output);
});
```

`TemplateRendering` and `PageTemplateRendering` can cancel rendering by setting the event invalid. `TemplateRendered` and `PageTemplateRendered` can mutate the rendered output.

## Native Blade Features

Craft does not wrap features Blade and Laravel already provide. Use the native Blade syntax for:

- escaped output with `{{ }}`
- raw output with `{!! !!}`
- control flow like `@if`, `@foreach`, `@switch`, and `@class`
- includes, layouts, components, and stacks
- `@auth`, `@guest`, and `@can`
- `@csrf`, `@method`, `@vite`, `@dd`, and other Laravel directives
- Laravel view composers

## Security

Blade templates are trusted code. They can execute PHP and call application services. Do not render untrusted user-editable Blade templates or strings.

Twig remains the safe path for sandboxed or user-authored templates.
