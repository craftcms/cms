<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Entries;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Twig\Variables\Paginate;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Database\Query\Builder;
use Stringable;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template as TwigTemplate;
use Twig\TemplateWrapper;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

class Template
{
    /** @var array<string, mixed> */
    protected static array $_fallbacks = [];

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

    public static function variableExists(string $name, array $context): bool
    {
        return array_key_exists($name, $context) || static::fallbackValueExists($name);
    }

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
            if ($object instanceof BaseObject && $object->canGetProperty($item)) {
                if ($isDefinedTest) {
                    return true;
                }

                if ($sandboxed) {
                    $env->getExtension(SandboxExtension::class)->checkPropertyAllowed($object, $item, $lineno, $source);
                }

                return $object->$item;
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

    /** @throws InvalidConfigException */
    public static function js(string $js, array $options = [], ?string $key = null): void
    {
        if (preg_match('/^[^\r\n]+\.js(\.gz)?$/i', $js) || Url::isAbsoluteUrl($js)) {
            HtmlStack::jsFile($js, $options, $key);

            return;
        }

        $position = Position::tryFrom($options['position'] ?? Position::BodyEnd->value) ?? Position::BodyEnd;
        HtmlStack::js($js, $position, $key);
    }

    public static function script(string $script, int|Position $position = Position::BodyEnd): void
    {
        if (is_int($position)) {
            $position = Position::from($position);
        }

        HtmlStack::script($script, $position);
    }

    public static function contextWithoutTemplate(array $context): array
    {
        return array_filter($context, fn ($value) => ! $value instanceof TwigTemplate && ! $value instanceof TemplateWrapper);
    }

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

    public static function paginateQuery($query): array
    {
        /** @var Builder $query */
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
