<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\Events\RenderingAssets;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;
use Stringable;

/**
 * Manages the registration and rendering of front-end assets (JavaScript, CSS, HTML, meta tags, etc.)
 * for a single request lifecycle.
 *
 * Assets are registered via dedicated methods (e.g. [[js()]], [[css()]], [[jsFile()]]) and rendered
 * into the page via [[headHtml()]] and [[bodyHtml()]]. A per-key buffering system allows capturing
 * assets registered during a block of code without them appearing in the final output.
 */
#[Scoped]
class HtmlStack
{
    /** @var array<int, array<string, string>> */
    private array $js = [];

    /** @var array<int, array<string, Stringable|string>> */
    private array $scripts = [];

    /** @var array<int, array<string, Stringable|string>> */
    private array $jsFiles = [];

    /** @var array<string, Stringable|string> */
    private array $cssFiles = [];

    /** @var array<string, Stringable|string> */
    private array $css = [];

    /** @var array<int, array<string, string>> */
    private array $html = [];

    /** @var array<string, string> */
    private array $jsImports = [];

    /** @var array<string, Stringable|string> */
    private array $metaTags = [];

    /** @var array<string, Stringable|string> */
    private array $linkTags = [];

    /** @var list<string> */
    private array $icons = [];

    /**
     * Per-key buffer stacks. Each key (e.g. 'js', 'css') has its own
     * independent stack, allowing different asset types to be buffered
     * and restored independently. Nesting is supported per key.
     *
     * @var array<string, list<mixed>>
     */
    private array $buffers = [];

    /**
     * Registers inline JavaScript code.
     *
     * The code will be rendered inside a `<script>` tag when [[headHtml()]] or [[bodyHtml()]]
     * is called, depending on the given position. If the same key is registered twice, the
     * latter value overwrites the former.
     *
     * @param  string  $js  The JavaScript code to register.
     * @param  Position  $position  Where on the page the code should appear.
     * @param  string|null  $key  A unique key for deduplication. Defaults to a hash of `$js`.
     */
    public function js(string $js, Position $position = Position::BodyEnd, ?string $key = null): void
    {
        $js = Str::finish(trim($js), ';');

        $this->js[$position->value][$key ?? md5($js)] = $js;
    }

    /**
     * Registers inline JavaScript code using a callback and pre-JSON-encoded variables.
     *
     * Each value in `$vars` is JSON-encoded and passed to `$fn` as positional arguments.
     * The string returned by `$fn` is then registered via [[js()]].
     *
     * @param  callable  $fn  A callback that receives the JSON-encoded variables and returns JS code.
     * @param  array  $vars  Variables to JSON-encode and pass to `$fn`.
     * @param  Position  $position  Where on the page the code should appear.
     * @param  string|null  $key  A unique key for deduplication. Defaults to a hash of the resulting JS.
     */
    public function jsWithVars(callable $fn, array $vars, Position $position = Position::BodyEnd, ?string $key = null): void
    {
        $encodedVars = array_map(fn (mixed $var): string => Json::encode($var), $vars);
        $js = $fn(...array_values($encodedVars));

        $this->js($js, $position, $key);
    }

    /**
     * Registers an external JavaScript file.
     *
     * The file is rendered as a `<script src="...">` tag. Position can be set via an
     * `options['position']` key (defaults to [[Position::Body]]). All other options are
     * passed through as HTML attributes on the `<script>` tag.
     *
     * @param  string  $url  The URL of the JavaScript file.
     * @param  array  $options  HTML attributes and options. A `position` key sets the page position.
     * @param  string|null  $key  A unique key for deduplication. Defaults to `$url`.
     */
    public function jsFile(string $url, array $options = [], ?string $key = null): void
    {
        $position = Position::tryFrom((int) Arr::pull($options, 'position', Position::BodyEnd->value)) ?? Position::BodyEnd;

        $this->jsFiles[$position->value][$key ?? $url] = Html::javaScriptFile($url, $options);
    }

    /**
     * Registers an external CSS stylesheet file.
     *
     * The file is rendered as a `<link rel="stylesheet">` tag in the `<head>`.
     *
     * @param  string  $url  The URL of the CSS file.
     * @param  array  $options  HTML attributes to add to the `<link>` tag.
     * @param  string|null  $key  A unique key for deduplication. Defaults to `$url`.
     */
    public function cssFile(string $url, array $options = [], ?string $key = null): void
    {
        $this->cssFiles[$key ?? $url] = Html::cssFile($url, $options);
    }

    /**
     * Registers inline CSS code.
     *
     * The code is rendered inside a `<style>` tag in the `<head>`.
     *
     * @param  string  $css  The CSS code to register.
     * @param  array  $options  HTML attributes to add to the `<style>` tag.
     * @param  string|null  $key  A unique key for deduplication. Defaults to a hash of `$css`.
     */
    public function css(string $css, array $options = [], ?string $key = null): void
    {
        $this->css[$key ?? md5($css)] = Html::style($css, $options);
    }

    /**
     * Registers an inline `<script>` tag with arbitrary content (e.g. JSON-LD, application/ld+json).
     *
     * Unlike [[js()]], the content is not assumed to be executable JavaScript and will not
     * have a semicolon appended. Use this for structured data or non-standard script types.
     *
     * @param  string  $script  The content of the `<script>` tag.
     * @param  Position  $position  Where on the page the tag should appear.
     * @param  array  $options  HTML attributes to add to the `<script>` tag (e.g. `['type' => 'application/ld+json']`).
     * @param  string|null  $key  A unique key for deduplication. Defaults to a hash of `$script`.
     */
    public function script(string $script, Position $position = Position::BodyEnd, array $options = [], ?string $key = null): void
    {
        $this->scripts[$position->value][$key ?? md5($script)] = Html::script($script, $options);
    }

    /**
     * Registers an inline `<script>` tag using a callback and pre-JSON-encoded variables.
     *
     * Each value in `$vars` is JSON-encoded and passed to `$fn` as positional arguments.
     * The string returned by `$fn` is then registered via [[script()]].
     *
     * @param  callable  $fn  A callback that receives the JSON-encoded variables and returns script content.
     * @param  array  $vars  Variables to JSON-encode and pass to `$fn`.
     * @param  Position  $position  Where on the page the tag should appear.
     * @param  array  $options  HTML attributes to add to the `<script>` tag.
     * @param  string|null  $key  A unique key for deduplication. Defaults to a hash of the resulting script.
     */
    public function scriptWithVars(callable $fn, array $vars, Position $position = Position::BodyEnd, array $options = [], ?string $key = null): void
    {
        $encodedVars = array_map(fn (mixed $var): string => Json::encode($var), $vars);
        $script = $fn(...array_values($encodedVars));

        $this->script($script, $position, $options, $key);
    }

    /**
     * Registers arbitrary HTML to be rendered in the page.
     *
     * @param  string  $html  The HTML to register.
     * @param  Position  $position  Where on the page the HTML should appear.
     * @param  string|null  $key  A unique key for deduplication. Defaults to a hash of `$html`.
     */
    public function html(string $html, Position $position = Position::BodyEnd, ?string $key = null): void
    {
        $this->html[$position->value][$key ?? md5($html)] = $html;
    }

    /**
     * Registers a JavaScript import mapping for the import map.
     *
     * Registered imports are rendered as a `<script type="importmap">` tag in [[headHtml()]].
     *
     * @param  string  $key  The module specifier (e.g. `lodash`).
     * @param  string  $value  The URL the specifier should resolve to.
     */
    public function jsImport(string $key, string $value): void
    {
        $this->jsImports[$key] = $value;
    }

    /**
     * Registers icon SVGs to be injected as `Craft.icons` in the page's JavaScript.
     *
     * Duplicate icon names are automatically deduplicated.
     *
     * @param  list<string>  $icons  The icon names to register.
     */
    public function icons(array $icons): void
    {
        $this->icons = array_values(array_unique([...$this->icons, ...$icons]));
    }

    /**
     * Registers a `<meta>` tag.
     *
     * The tag is rendered in [[headHtml()]].
     *
     * @param  array  $attributes  The HTML attributes for the `<meta>` tag (e.g. `['name' => 'description', 'content' => '...']`).
     * @param  string|null  $key  A unique key for deduplication. Defaults to a hash of the serialized attributes.
     */
    public function metaTag(array $attributes, ?string $key = null): void
    {
        $this->metaTags[$key ?? md5(serialize($attributes))] = Html::tag('meta', attributes: $attributes);
    }

    /**
     * Registers a `<link>` tag.
     *
     * The tag is rendered in [[headHtml()]].
     *
     * @param  array  $attributes  The HTML attributes for the `<link>` tag (e.g. `['rel' => 'canonical', 'href' => '...']`).
     * @param  string|null  $key  A unique key for deduplication. Defaults to a hash of the serialized attributes.
     */
    public function linkTag(array $attributes, ?string $key = null): void
    {
        $this->linkTags[$key ?? md5(serialize($attributes))] = Html::tag('link', attributes: $attributes);
    }

    /**
     * Renders all assets registered for the `<head>` section of the page.
     *
     * Output order: import map, scripts, HTML, meta tags, link tags, CSS files,
     * inline CSS, JS files, and inline JS.
     *
     * By default, rendered assets are cleared from the registry after output.
     *
     * @param  bool  $clear  Whether to clear the rendered head assets from the registry.
     * @return string The rendered HTML for the `<head>`.
     */
    public function headHtml(bool $clear = true): string
    {
        event(new RenderingAssets);

        $head = Position::Head->value;

        $parts = collect()
            ->when(
                $this->jsImports,
                fn (Collection $c) => $c->concat([
                    '<script type="importmap">{"imports": '.Json::encode($this->jsImports, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'}</script>',
                ]),
            )
            ->concat($this->scripts[$head] ?? [])
            ->concat($this->html[$head] ?? [])
            ->concat($this->metaTags)
            ->concat($this->linkTags)
            ->concat($this->cssFiles)
            ->concat($this->css)
            ->concat($this->jsFiles[$head] ?? [])
            ->unless(
                empty($this->js[$head]),
                fn (Collection $c) => $c->concat([
                    Html::script(implode(PHP_EOL, $this->js[$head])),
                ]),
            )
            ->map(fn (string|Stringable $part) => (string) $part);

        if ($clear) {
            $this->jsImports = [];
            $this->metaTags = [];
            $this->linkTags = [];
            $this->cssFiles = [];
            $this->css = [];

            unset($this->scripts[$head], $this->html[$head], $this->jsFiles[$head], $this->js[$head]);
        }

        return $parts->implode(PHP_EOL);
    }

    public function bodyHtml(bool $clear = true): string
    {
        return $this->bodyBeginHtml($clear).$this->bodyEndHtml($clear);
    }

    /**
     * Renders all assets registered for the start of the `<body>` section of the page.
     *
     * Output order: scripts, HTML, JS files, and inline JS.
     *
     * By default, rendered assets are cleared from the registry after output.
     *
     * @param  bool  $clear  Whether to clear the rendered body assets from the registry.
     * @return string The rendered HTML for the begin of the `<body>`.
     */
    public function bodyBeginHtml(bool $clear = true): string
    {
        event(new RenderingAssets);

        $body = Position::BodyBegin->value;

        $parts = $this->getAssetsForPosition(Position::BodyBegin);

        if ($clear) {
            unset($this->scripts[$body], $this->html[$body], $this->jsFiles[$body], $this->js[$body]);
        }

        return $parts->implode(PHP_EOL);
    }

    /**
     * Renders all assets registered for the end of the `<body>` section of the page.
     *
     * Output order: scripts, HTML, JS files, and inline JS. Registered icons are
     * serialized into a `Craft.icons` JS assignment before rendering.
     *
     * By default, rendered assets are cleared from the registry after output.
     *
     * @param  bool  $clear  Whether to clear the rendered body assets from the registry.
     * @return string The rendered HTML for the end of the `<body>`.
     */
    public function bodyEndHtml(bool $clear = true): string
    {
        event(new RenderingAssets);

        $body = Position::BodyEnd->value;

        if (! empty($this->icons)) {
            $icons = collect($this->icons)
                ->mapWithKeys(fn (string $icon) => [$icon => Icons::svg($icon)])
                ->all();

            $iconsJs = Json::encode($icons);
            $this->js("Craft.icons = $iconsJs;");
        }

        $parts = $this->getAssetsForPosition(Position::BodyEnd);

        if ($clear) {
            $this->icons = [];

            unset($this->scripts[$body], $this->html[$body], $this->jsFiles[$body], $this->js[$body]);
        }

        return $parts->implode(PHP_EOL);
    }

    /**
     * Begins buffering one or more asset property keys.
     *
     * For each key, the current state is pushed onto a stack and then emptied so that
     * any new registrations are captured in isolation. Nesting is supported — each key
     * maintains its own independent stack depth.
     *
     * @param  array|string  $keys  The property names to buffer (e.g. `'js'`, `['css', 'cssFiles']`).
     */
    public function startBuffer(array|string $keys): void
    {
        foreach (Arr::wrap($keys) as $key) {
            $this->buffers[$key][] = $this->{$key};
            $this->{$key} = [];
        }
    }

    /**
     * Clears and ends a buffer started via [[startBuffer()]], returning any assets that were
     * registered while the buffer was active.
     *
     * The previous state (before the buffer was started) is restored. When a single key is
     * given, its captured value is returned directly. When multiple keys are given, an
     * associative array keyed by property name is returned.
     *
     * @param  array|string  $keys  One or more property keys to clear (must match the keys passed to [[startBuffer()]]).
     * @return mixed The captured assets — a single value for a single key, or `['key' => value, ...]` for multiple keys.
     *
     * @see startBuffer()
     */
    public function clearBuffer(array|string $keys): mixed
    {
        $captured = [];

        $keys = Arr::wrap($keys);

        foreach ($keys as $key) {
            $captured[$key] = $this->{$key};
            $this->{$key} = ! empty($this->buffers[$key])
                ? array_pop($this->buffers[$key])
                : [];
        }

        if (count($keys) === 1) {
            return $captured[$keys[0]];
        }

        return $captured;
    }

    /**
     * Starts a buffer for any inline CSS registered with [[css()]].
     *
     * The buffer's contents can be cleared and returned later via [[clearCssBuffer()]].
     *
     * @see clearCssBuffer()
     */
    public function startCssBuffer(): void
    {
        $this->startBuffer('css');
    }

    /**
     * Clears and ends a buffer started via [[startCssBuffer()]], returning any inline CSS
     * that was registered while the buffer was active.
     *
     * @return array<string, Stringable|string> The CSS entries keyed by their registration key.
     *
     * @see startCssBuffer()
     */
    public function clearCssBuffer(): array
    {
        return $this->clearBuffer('css');
    }

    /**
     * Starts a buffer for any CSS files registered with [[cssFile()]].
     *
     * The buffer's contents can be cleared and returned later via [[clearCssFileBuffer()]].
     *
     * @see clearCssFileBuffer()
     */
    public function startCssFileBuffer(): void
    {
        $this->startBuffer('cssFiles');
    }

    /**
     * Clears and ends a buffer started via [[startCssFileBuffer()]], returning any CSS file
     * registrations that were captured while the buffer was active.
     *
     * @return array<string, Stringable|string> The CSS file entries keyed by their registration key.
     *
     * @see startCssFileBuffer()
     */
    public function clearCssFileBuffer(): array
    {
        return $this->clearBuffer('cssFiles');
    }

    /**
     * Starts a buffer for any HTML registered with [[html()]].
     *
     * The buffer's contents can be cleared and returned later via [[clearHtmlBuffer()]].
     *
     * @see clearHtmlBuffer()
     */
    public function startHtmlBuffer(): void
    {
        $this->startBuffer('html');
    }

    /**
     * Clears and ends a buffer started via [[startHtmlBuffer()]], returning any HTML
     * that was registered while the buffer was active.
     *
     * @return array<int, array<string, string>> The HTML entries keyed by position and registration key.
     *
     * @see startHtmlBuffer()
     */
    public function clearHtmlBuffer(): array|false
    {
        return $this->clearBuffer('html');
    }

    /**
     * Starts a buffer for any inline JavaScript registered with [[js()]].
     *
     * The buffer's contents can be cleared and returned later via [[clearJsBuffer()]].
     *
     * @see clearJsBuffer()
     */
    public function startJsBuffer(): void
    {
        $this->startBuffer('js');
    }

    /**
     * Clears and ends a buffer started via [[startJsBuffer()]], returning any JavaScript code
     * that was registered while the buffer was active.
     *
     * By default, all captured JS is combined into a single string wrapped in a `<script>` tag.
     * Set `$combine` to `false` to receive an array keyed by [[Position]] value.
     * Set `$scriptTag` to `false` to receive raw JS without `<script>` tag wrapping.
     *
     * @param  bool  $scriptTag  Whether the returned code should be wrapped in a `<script>` tag.
     * @param  bool  $combine  Whether all positions should be combined into a single blob (position info is lost).
     * @return string|array The captured JS — a string when combined, or an array keyed by position value when not.
     *
     * @see startJsBuffer()
     */
    public function clearJsBuffer(bool $scriptTag = true, bool $combine = true): string|array
    {
        $bufferedJs = $this->clearBuffer('js');

        if ($combine) {
            $js = '';

            foreach (Position::cases() as $pos) {
                if (! empty($bufferedJs[$pos->value])) {
                    $js .= implode("\n", $bufferedJs[$pos->value])."\n";
                }
            }

            if ($scriptTag && ! empty($js)) {
                return Html::script($js, ['type' => 'text/javascript'])->render();
            }

            return $js;
        }

        if ($scriptTag) {
            foreach ($bufferedJs as $pos => $js) {
                $bufferedJs[$pos] = Html::script(implode("\n", $js), ['type' => 'text/javascript'])->render();
            }
        }

        return $bufferedJs;
    }

    /**
     * Starts a buffer for any JavaScript files registered with [[jsFile()]].
     *
     * The buffer's contents can be cleared and returned later via [[clearJsFileBuffer()]].
     *
     * @see clearJsFileBuffer()
     */
    public function startJsFileBuffer(): void
    {
        $this->startBuffer('jsFiles');
    }

    /**
     * Clears and ends a buffer started via [[startJsFileBuffer()]], returning any JavaScript file
     * registrations that were captured while the buffer was active.
     *
     * @return array<int, array<string, Stringable|string>> The JS file entries keyed by position and registration key.
     *
     * @see startJsFileBuffer()
     */
    public function clearJsFileBuffer(): array
    {
        return $this->clearBuffer('jsFiles');
    }

    /**
     * Starts a buffer for any JavaScript import mappings registered with [[jsImport()]].
     *
     * The buffer's contents can be cleared and returned later via [[clearJsImportBuffer()]].
     *
     * @see clearJsImportBuffer()
     */
    public function startJsImportBuffer(): void
    {
        $this->startBuffer('jsImports');
    }

    /**
     * Clears and ends a buffer started via [[startJsImportBuffer()]], returning any JavaScript
     * import mappings that were registered while the buffer was active.
     *
     * @return array<string, string> The import map entries keyed by module specifier.
     *
     * @see startJsImportBuffer()
     */
    public function clearJsImportBuffer(): array
    {
        return $this->clearBuffer('jsImports');
    }

    /**
     * Starts a buffer for any `<meta>` tags registered with [[metaTag()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearMetaTagBuffer()]].
     *
     * @see clearMetaTagBuffer()
     */
    public function startMetaTagBuffer(): void
    {
        $this->startBuffer('metaTags');
    }

    /**
     * Clears and ends a buffer started via [[startMetaTagBuffer()]], returning any `<meta>` tags
     * that were registered while the buffer was active.
     *
     * @return array<string, Stringable|string> The meta tag entries keyed by their registration key.
     *
     * @see startMetaTagBuffer()
     */
    public function clearMetaTagBuffer(): array
    {
        return $this->clearBuffer('metaTags');
    }

    /**
     * Starts a buffer for any `<script>` tags registered with [[script()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearScriptBuffer()]].
     *
     * @see clearScriptBuffer()
     */
    public function startScriptBuffer(): void
    {
        $this->startBuffer('scripts');
    }

    /**
     * Clears and ends a buffer started via [[startScriptBuffer()]], returning any `<script>` tags
     * that were registered while the buffer was active.
     *
     * @return array<int, array<string, Stringable|string>> The script entries keyed by position and registration key.
     *
     * @see startScriptBuffer()
     */
    public function clearScriptBuffer(): array
    {
        return $this->clearBuffer('scripts');
    }

    /**
     * Applies previously captured buffer state back into the registry, merging with any
     * existing registrations.
     *
     * Position-keyed properties (`js`, `scripts`, `jsFiles`, `html`) are merged per-position.
     * Flat-keyed properties (`cssFiles`, `css`, `jsImports`, `metaTags`, `linkTags`) are
     * merged by key. Icons are deduplicated and appended.
     *
     * @param  array<string, mixed>  $buffer  The captured state from [[clearBuffer()]], keyed by property name.
     */
    public function applyBuffer(array $buffer): void
    {
        foreach (['js', 'scripts', 'jsFiles', 'html'] as $property) {
            foreach ($buffer[$property] ?? [] as $position => $entries) {
                foreach ($entries as $key => $value) {
                    $this->{$property}[$position][$key] = $value;
                }
            }
        }

        foreach (['cssFiles', 'css', 'jsImports', 'metaTags', 'linkTags'] as $property) {
            foreach ($buffer[$property] ?? [] as $key => $value) {
                $this->{$property}[$key] = $value;
            }
        }

        if (! empty($buffer['icons'])) {
            $this->icons = array_values(array_unique([...$this->icons, ...$buffer['icons']]));
        }
    }

    /**
     * Resets all registered assets and active buffers, returning the registry to a clean state.
     */
    public function clear(): void
    {
        $this->buffers = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->html = [];
        $this->icons = [];
        $this->js = [];
        $this->jsFiles = [];
        $this->jsImports = [];
        $this->linkTags = [];
        $this->metaTags = [];
        $this->scripts = [];
    }

    private function getAssetsForPosition(Position $position): Collection
    {
        return collect()
            ->concat($this->scripts[$position->value] ?? [])
            ->concat($this->html[$position->value] ?? [])
            ->concat($this->jsFiles[$position->value] ?? [])
            ->unless(
                empty($this->js[$position->value]),
                fn (Collection $c) => $c->concat([
                    Html::script(implode(PHP_EOL, $this->js[$position->value]), ['type' => 'module']),
                ]),
            )
            ->map(fn (string|Stringable $part) => (string) $part);
    }
}
