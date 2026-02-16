<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use craft\helpers\Cp;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;
use Stringable;
use Yiisoft\Html\Html;

use function CraftCms\Cms\t;

#[Scoped]
final class AssetRegistry
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

    public function js(string $js, Position $position = Position::Body, ?string $key = null): void
    {
        $js = Str::finish(trim($js), ';');

        $this->js[$position->value][$key ?? md5($js)] = $js;
    }

    public function jsWithVars(callable $fn, array $vars, Position $position = Position::Body, ?string $key = null): void
    {
        $encodedVars = array_map(fn (mixed $var): string => Json::encode($var), $vars);
        $js = $fn(...array_values($encodedVars));

        $this->js($js, $position, $key);
    }

    public function jsFile(string $url, array $options = [], ?string $key = null): void
    {
        $position = Position::tryFrom((int) Arr::pull($options, 'position', Position::Body->value)) ?? Position::Body;

        $this->jsFiles[$position->value][$key ?? $url] = Html::javaScriptFile($url, $options);
    }

    public function cssFile(string $url, array $options = [], ?string $key = null): void
    {
        $this->cssFiles[$key ?? $url] = Html::cssFile($url, $options);
    }

    public function css(string $css, array $options = [], ?string $key = null): void
    {
        $this->css[$key ?? md5($css)] = Html::style($css, $options);
    }

    public function script(string $script, Position $position = Position::Body, array $options = [], ?string $key = null): void
    {
        $this->scripts[$position->value][$key ?? md5($script)] = Html::script($script, $options);
    }

    public function scriptWithVars(callable $fn, array $vars, Position $position = Position::Body, array $options = [], ?string $key = null): void
    {
        $encodedVars = array_map(fn (mixed $var): string => Json::encode($var), $vars);
        $script = $fn(...array_values($encodedVars));

        $this->script($script, $position, $options, $key);
    }

    public function html(string $html, Position $position = Position::Body, ?string $key = null): void
    {
        $this->html[$position->value][$key ?? md5($html)] = $html;
    }

    public function jsImport(string $key, string $value): void
    {
        $this->jsImports[$key] = $value;
    }

    public function translations(string $category, array $messages): void
    {
        $jsCategory = Json::encode($category);

        $lines = collect($messages)
            ->map(function (string $message) use ($jsCategory, $category): ?string {
                $translation = t($message, category: $category);

                if ($translation === $message) {
                    return null;
                }

                $jsMessage = Json::encode($message);
                $jsTranslation = Json::encode($translation);

                return "Craft.translations[$jsCategory][$jsMessage] = $jsTranslation;";
            })
            ->whereNotNull();

        if ($lines->isEmpty()) {
            return;
        }

        $assignments = $lines->implode(PHP_EOL);

        $this->js(<<<JS
        if (typeof Craft.translations[$jsCategory] === 'undefined') {
            Craft.translations[$jsCategory] = {};
        }
        $assignments
        JS);
    }

    /**
     * @param  list<string>  $icons
     */
    public function icons(array $icons): void
    {
        $this->icons = array_values(array_unique([...$this->icons, ...$icons]));
    }

    public function metaTag(array $attributes, ?string $key = null): void
    {
        $this->metaTags[$key ?? md5(serialize($attributes))] = Html::tag('meta', attributes: $attributes);
    }

    public function linkTag(array $attributes, ?string $key = null): void
    {
        $this->linkTags[$key ?? md5(serialize($attributes))] = Html::tag('link', attributes: $attributes);
    }

    public function headHtml(bool $clear = true): string
    {
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
        $body = Position::Body->value;

        if (! empty($this->icons)) {
            $icons = collect($this->icons)
                ->mapWithKeys(fn (string $icon) => [$icon => Cp::iconSvg($icon)])
                ->all();

            $iconsJs = Json::encode($icons);
            $this->js("Craft.icons = $iconsJs;");
        }

        $parts = collect()
            ->concat($this->scripts[$body] ?? [])
            ->concat($this->html[$body] ?? [])
            ->concat($this->jsFiles[$body] ?? [])
            ->unless(
                empty($this->js[$body]),
                fn (Collection $c) => $c->concat([
                    Html::script(implode(PHP_EOL, $this->js[$body])),
                ]),
            )
            ->map(fn (string|Stringable $part) => (string) $part);

        if ($clear) {
            $this->icons = [];

            unset($this->scripts[$body], $this->html[$body], $this->jsFiles[$body], $this->js[$body]);
        }

        return $parts->implode(PHP_EOL);
    }

    /**
     * Starts a buffer for the specified array property keys. Each key gets
     * its current state pushed onto an independent stack, and is then emptied.
     * Nesting is supported — each key has its own stack depth.
     *
     * @param  list<string>  $keys  The property names to buffer (e.g. ['js'], ['css', 'cssFiles'])
     */
    public function startBuffer(array|string $keys): void
    {
        foreach (Arr::wrap($keys) as $key) {
            $this->buffers[$key][] = $this->{$key};
            $this->{$key} = [];
        }
    }

    /**
     * Ends a buffer for the specified array property keys. For each key,
     * captures the current value (what was registered during the buffer),
     * pops and restores the previous state from the stack.
     *
     * @param  list<string>  $keys  The property names to clear (must match a previous startBuffer call)
     * @return array<string, mixed> The captured state for each key
     */
    public function clearBuffer(array|string $keys): array
    {
        $captured = [];

        foreach (Arr::wrap($keys) as $key) {
            $captured[$key] = $this->{$key};
            $this->{$key} = ! empty($this->buffers[$key])
                ? array_pop($this->buffers[$key])
                : [];
        }

        return $captured;
    }

    /**
     * Applies previously captured buffer state back into the registry,
     * merging with any existing registrations.
     *
     * @param  array<string, mixed>  $buffer  The captured state from clearBuffer()
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
     * Resets all registered assets back to their initial state.
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
}
