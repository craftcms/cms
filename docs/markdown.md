# Markdown

Craft 6 includes a Laravel-native Markdown service built on [league/commonmark](https://commonmark.thephpleague.com/).

The service lives in `CraftCms\Cms\Markdown\Markdown`, is exposed via `CraftCms\Cms\Support\Facades\Markdown`, and supports named Markdown flavors which resolve to CommonMark converters.

## Basic Usage

Use the facade when you need to convert Markdown to HTML:

```php
<?php

use CraftCms\Cms\Support\Facades\Markdown;

$html = Markdown::parse('**Hello**');
// <p><strong>Hello</strong></p>

$inlineHtml = Markdown::parseParagraph('**Hello**');
// <strong>Hello</strong>
```

The main methods are:

- `Markdown::parse()` full Markdown-to-HTML rendering
- `Markdown::parseParagraph()` inline-only rendering with no wrapping `<p>` tags
- `Markdown::convert()` low-level rendering with a `MarkdownOptions` object

## Built-In Flavors

Craft registers these flavors by default in `src/Markdown/Markdown.php`:

- `original` CommonMark-style rendering
- `gfm` GitHub-flavored Markdown
- `gfm-comment` GitHub-flavored Markdown with `<br>` soft line breaks for comment-style text
- `extra` CommonMark + table support for legacy Markdown Extra compatibility
- `pre-encoded` compatibility mode for Markdown that has already been HTML-encoded before parsing

Use a flavor by passing its name to `parse()` or `parseParagraph()`:

```php
<?php

use CraftCms\Cms\Support\Facades\Markdown;

$html = Markdown::parse($text, 'gfm');
$commentHtml = Markdown::parse($text, 'gfm-comment');
```

If no flavor is specified, Craft uses `original`.

## Inline-Only Rendering

Use `parseParagraph()` when you only want inline Markdown elements like emphasis, code spans, and links:

```php
<?php

use CraftCms\Cms\Support\Facades\Markdown;

$html = Markdown::parseParagraph('A **bold** word and a [link](https://craftcms.com).');
```

This uses CommonMark's `InlinesOnlyExtension` under the hood.

## Unsafe Link Handling

By default, Craft configures CommonMark with `allow_unsafe_links = false`, so links like `javascript:` are not rendered with unsafe `href` attributes.

If you need to allow them for backwards compatibility, pass `true` as the third argument:

```php
<?php

use CraftCms\Cms\Support\Facades\Markdown;

$html = Markdown::parse($markdown, allowUnsafeLinks: true);
```

This option exists to preserve the legacy `parseJavaScriptLinks` opt-in behavior exposed by the yii2-adapter Markdown classes.

## Extending Markdown

You can register custom flavors with `Markdown::extend()`.

Each flavor is a callable that receives `CraftCms\Cms\Markdown\MarkdownOptions` and returns a configured `League\CommonMark\MarkdownConverter`.

```php
<?php

use CraftCms\Cms\Markdown\Flavors\GfmFlavor;
use CraftCms\Cms\Markdown\MarkdownOptions;
use CraftCms\Cms\Support\Facades\Markdown;

Markdown::extend('gfm-softbreaks', new GfmFlavor("<br>\n"));

Markdown::extend('custom', function (MarkdownOptions $options) {
    return new GfmFlavor("<br>\n")($options);
});
```

After registration:

```php
<?php

use CraftCms\Cms\Support\Facades\Markdown;

$html = Markdown::parse($text, 'custom');
```

## Writing Custom Flavors

Craft's built-in flavors live in `src/Markdown/Flavors/`.

Available classes:

- `CommonMarkFlavor`
- `GfmFlavor`
- `ExtraFlavor`

They are invokable classes, so you can use them directly or create your own.

If you want to build a custom flavor class, extend `CraftCms\Cms\Markdown\Flavors\Flavor`:

```php
<?php

namespace App\Markdown;

use CraftCms\Cms\Markdown\Flavors\Flavor;
use CraftCms\Cms\Markdown\MarkdownOptions;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

class CustomFlavor extends Flavor
{
    public function __invoke(MarkdownOptions $options): MarkdownConverter
    {
        $environment = $this->environment($options, "<br>\n");
        $environment->addExtension(new CommonMarkCoreExtension());

        return new MarkdownConverter($environment);
    }
}
```

Then register it:

```php
<?php

use App\Markdown\CustomFlavor;
use CraftCms\Cms\Support\Facades\Markdown;

Markdown::extend('custom', new CustomFlavor());
```

## Low-Level Options

If you need full control over rendering mode, use `MarkdownOptions` directly:

```php
<?php

use CraftCms\Cms\Markdown\MarkdownOptions;
use CraftCms\Cms\Support\Facades\Markdown;

$html = Markdown::convert(
    $markdown,
    new MarkdownOptions(
        flavor: 'gfm',
        inlineOnly: false,
        allowUnsafeLinks: false,
    ),
);
```

`MarkdownOptions` supports:

- `flavor` the registered flavor name
- `inlineOnly` whether to render only inline elements
- `allowUnsafeLinks` whether CommonMark should allow unsafe link targets

## Pre-Encoded Compatibility

The `pre-encoded` flavor exists for compatibility with legacy Craft/Yii behavior where the Markdown input has already been HTML-encoded before parsing.

This matters most for code spans and code blocks, because normal CommonMark will escape already-encoded code content again.

For example, normal CommonMark will turn:

```md
`&lt;b&gt;`
```

into HTML containing `&amp;lt;b&amp;gt;` inside `<code>`.

Craft's `pre-encoded` flavor uses a small custom CommonMark extension in `src/Markdown/CommonMark/Extensions/PreEncodedExtension.php` to preserve already-encoded code content without double-escaping it.

If you do not need that legacy compatibility, prefer the standard `original`, `gfm`, or `extra` flavors.

## Adapter Compatibility

Legacy parser classes still exist in `yii2-adapter/legacy/markdown/`, but they now forward to the new Markdown service.

The compatibility toggles that still affect rendering are:

- `parseJavaScriptLinks` on the legacy parser classes
- `enableNewlines` on `GithubMarkdown`

The legacy `html5` and `codeAttributesOnPre` properties no longer map to CommonMark options. If they are changed from their defaults, Craft logs a deprecation warning and ignores them.

Those classes are deprecated. New code should call the Markdown facade or resolve `CraftCms\Cms\Markdown\Markdown` from the container directly.

## Testing

Relevant tests live in:

- `tests/Unit/Markdown/MarkdownTest.php`
- `tests/Unit/Markdown/CommonMark/PreEncodedExtensionTest.php`
- `tests/Unit/Markdown/CommonMark/PreEncodedRenderersTest.php`
- `tests/Unit/Cp/ContentHtmlTest.php`
- `tests/Unit/Twig/Extensions/HtmlTwigExtensionTest.php`
- `yii2-adapter/tests-laravel/Legacy/Markdown/LegacyMarkdownTest.php`

Run the focused tests with:

```bash
./vendor/bin/pest --parallel --compact tests/Unit/Markdown/MarkdownTest.php
./vendor/bin/pest --parallel --compact tests/Unit/Markdown/CommonMark/PreEncodedExtensionTest.php
./vendor/bin/pest --parallel --compact tests/Unit/Markdown/CommonMark/PreEncodedRenderersTest.php
```
