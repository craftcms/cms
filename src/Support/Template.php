<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\Renderable;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Entries;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Twig\Variables\Paginate;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use RuntimeException;
use Stringable;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template as TwigTemplate;
use Twig\TemplateWrapper;

/**
 * @since 6.0.0
 */
class Template
{
    /** @var array<string, mixed> */
    protected static array $_fallbacks = [];

    /** @param array<string, mixed> $context */
    public static function resolveVariable(string $name, array $context, bool $strict, int $lineno = -1, ?Source $source = null): mixed
    {
        if (isset($context[$name]) || array_key_exists($name, $context)) {
            return $context[$name];
        }

        if (static::fallbackValueExists($name)) {
            return static::fallbackValue($name);
        }

        if ($strict) {
            throw new RuntimeError("Variable \"$name\" does not exist.", $lineno, $source);
        }

        return null;
    }

    /** @param array<string, mixed> $context */
    public static function variableExists(string $name, array $context): bool
    {
        return array_key_exists($name, $context) || static::fallbackValueExists($name);
    }

    /** @param array<array-key, mixed> $arguments */
    public static function attribute(
        Environment $env,
        Source $source,
        mixed $object,
        mixed $item,
        array $arguments = [],
        string $type = TwigTemplate::ANY_CALL,
        bool $isDefinedTest = false,
        bool $ignoreStrictCheck = false,
        bool $sandboxed = false,
        int $lineno = -1,
    ): mixed {
        if ($object instanceof ElementInterface) {
            ElementCaches::collectCacheInfoForElement($object);
        }

        if ($type !== TwigTemplate::METHOD_CALL) {
            if ($object instanceof Component && $object->canGetProperty($item)) {
                if ($isDefinedTest) {
                    return true;
                }

                if ($sandboxed) {
                    $env->getExtension(SandboxExtension::class)->checkPropertyAllowed($object, $item, $lineno, $source);
                }

                try {
                    return $object->$item;
                } catch (UnknownPropertyException) {
                    return $object->$item();
                }
            }

            if ($object instanceof BaseModel && $object->hasAttribute($item)) {
                if ($isDefinedTest) {
                    return true;
                }

                if ($sandboxed) {
                    $env->getExtension(SandboxExtension::class)->checkPropertyAllowed($object, $item, $lineno, $source);
                }

                return $object->$item;
            }
        }

        foreach ($arguments as $key => $value) {
            if (is_object($value) && $value::class === Markup::class) {
                $arguments[$key] = (string) $value;
            }
        }

        if ($type !== TwigTemplate::METHOD_CALL && $item instanceof Stringable) {
            $item = (string) $item;
        }

        if ($type !== TwigTemplate::METHOD_CALL && is_string($item)) {
            if ($object instanceof ViewErrorBag) {
                if (method_exists($object, $item)) {
                    return CoreExtension::getAttribute(
                        $env,
                        $source,
                        $object,
                        $item,
                        $arguments,
                        $type,
                        $isDefinedTest,
                        $ignoreStrictCheck,
                        $sandboxed,
                        $lineno,
                    );
                }

                if ($object->hasBag($item)) {
                    return $isDefinedTest ? true : $object->getBag($item);
                }

                $object = $object->getBag('default');
            }

            if ($object instanceof MessageBagContract && ! method_exists($object, $item)) {
                return $isDefinedTest ? $object->has($item) : $object->get($item);
            }
        }

        return CoreExtension::getAttribute(
            $env,
            $source,
            $object,
            $item,
            $arguments,
            $type,
            $isDefinedTest,
            $ignoreStrictCheck,
            $sandboxed,
            $lineno,
        );
    }

    public static function raw(string $value): Markup
    {
        return new Markup($value, 'UTF-8');
    }

    /** @param array<string, mixed> $options */
    public static function css(string $css, array $options = [], ?string $key = null): void
    {
        if (preg_match('/^[^\r\n]+\.css(\.gz)?$/i', $css) || Url::isAbsoluteUrl($css)) {
            HtmlStack::cssFile($css, $options, $key);

            return;
        }

        HtmlStack::css($css, $options, $key);
    }

    public static function html(string $html, int|Position $position = Position::BodyEnd): void
    {
        if (is_int($position)) {
            $position = Position::from($position);
        }

        HtmlStack::html($html, $position);
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws RuntimeException
     */
    public static function js(string $js, array $options = [], ?string $key = null): void
    {
        if (preg_match('/^[^\r\n]+\.js(\.gz)?$/i', $js) || Url::isAbsoluteUrl($js)) {
            HtmlStack::jsFile($js, $options, $key);

            return;
        }

        $position = Position::tryFrom($options['position'] ?? Position::Ready->value) ?? Position::Ready;
        HtmlStack::js($js, $position, $key);
    }

    public static function script(string $script, int|Position $position = Position::BodyEnd): void
    {
        if (is_int($position)) {
            $position = Position::from($position);
        }

        HtmlStack::script($script, $position);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function contextWithoutTemplate(array $context): array
    {
        return array_filter($context, fn ($value) => ! $value instanceof TwigTemplate && ! $value instanceof TemplateWrapper);
    }

    /**
     * @param  string[]  $handles
     * @param  array<array-key, mixed>|null  $context
     *
     * @param-out array<array-key, mixed>|null $context
     */
    public static function preloadSingles(array $handles, ?array &$context = null): void
    {
        $globals = Twig::get()->getGlobals();
        $handles = array_diff($handles, array_keys($globals));

        if (empty($handles)) {
            return;
        }

        $singles = Entries::getSingleEntriesByHandle($handles);
        self::$_fallbacks += $singles;

        if ($context === null) {
            return;
        }

        $context += $singles;
    }

    /**
     * Renders the given renderable object(s).
     *
     * If an array or other iterable is passed, each of its items will be rendered and joined with newlines,
     * and a `loop` variable will be made available to each of them, like within `{% for %}` loops. Any `loop`
     * variable in `$context` will be replaced, but a `loop` variable in `$variables` will be left alone.
     *
     * @param  Renderable|iterable<Renderable>|null  $objects  The object(s) to render
     * @param  array<string,mixed>  $variables  Variables that should be passed to each object’s `render()` method
     * @param  array<string,mixed>  $context  Additional variables that should be passed to each object’s `render()`
     *                                        method, such as the current template context. `$variables` take precedence over them.
     *
     * @throws InvalidArgumentException if any of the objects aren’t renderable
     *
     * @since 6.0.0
     */
    public static function render(Renderable|iterable|null $objects, array $variables = [], array $context = []): HtmlString
    {
        // If `loop` was explicitly passed in, leave it alone
        $defineLoop = ! array_key_exists('loop', $variables);
        $variables = [...$context, ...$variables];

        if ($objects instanceof Renderable) {
            return $objects->render($variables);
        }

        if ($objects === null) {
            $objects = [];
        } elseif (is_array($objects)) {
            $objects = array_values($objects);
        } else {
            $objects = iterator_to_array($objects, false);
        }

        $length = count($objects);
        $output = [];

        foreach ($objects as $i => $object) {
            if (! $object instanceof Renderable) {
                throw new InvalidArgumentException(sprintf('%s isn’t renderable.', get_debug_type($object)));
            }

            if (! $defineLoop) {
                $output[] = (string) $object->render($variables);

                continue;
            }

            // Provide a `loop` variable, like {% for %} loops
            $output[] = (string) $object->render([
                ...$variables,
                'loop' => [
                    'parent' => $variables,
                    'index0' => $i,
                    'index' => $i + 1,
                    'revindex0' => $length - $i - 1,
                    'revindex' => $length - $i,
                    'first' => $i === 0,
                    'last' => $i === $length - 1,
                    'length' => $length,
                ],
            ]);
        }

        return new HtmlString(implode("\n", $output));
    }

    /** @return array{Paginate, list<mixed>} */
    public static function paginateQuery(Builder $query): array
    {
        $paginator = $query->paginate(pageName: $pageParam = Cms::config()->getPageTriggerParam());
        $paginator->appends(Arr::except(request()->query(), $pageParam));

        return [
            Paginate::create($paginator),
            $paginator->items(),
        ];
    }

    protected static function fallbackValueExists(string $name): bool
    {
        return isset(self::$_fallbacks[$name]);
    }

    protected static function fallbackValue(string $name): mixed
    {
        if (! static::fallbackValueExists($name)) {
            throw new UnknownPropertyException("$name is not defined as a fallback template variable.");
        }

        return self::$_fallbacks[$name];
    }
}
