<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Events\PageTemplateRendered;
use CraftCms\Cms\Twig\Events\RenderingPageTemplate;
use CraftCms\Cms\Twig\Events\RenderingTemplate;
use CraftCms\Cms\Twig\Events\TemplateRendered;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Log;
use Twig\Extension\SandboxExtension;
use Twig\Template;
use Twig\TemplateWrapper;
use Yiisoft\Arrays\ArrayableInterface;

/**
 * @mixin Twig
 */
#[Scoped]
class TemplateRenderer
{
    /** @var TemplateWrapper[] Object template cache */
    private array $objectTemplates = [];

    private ?string $renderingTemplate = null;

    public bool $isRenderingTemplate {
        get => $this->isRenderingTemplate();
    }

    public private(set) bool $isRenderingPageTemplate = false;

    public function __construct(
        private readonly Twig $twig,
        private readonly GeneralConfig $generalConfig,
        private readonly PageLifecycle $pageLifecycle,
    ) {}

    /**
     * Returns whether a template is currently being rendered.
     */
    public function isRenderingTemplate(): bool
    {
        return isset($this->renderingTemplate);
    }

    /**
     * Renders a Twig template.
     *
     * @param  string  $template  The name of the template to load
     * @param  array  $variables  The variables that should be available to the template
     * @param  ?TemplateMode  $templateMode  The template mode to use
     * @return string the rendering result
     */
    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null
    ): string {
        $templateMode ??= TemplateMode::get();

        event($event = new RenderingTemplate($template, $variables, $templateMode));

        if (! $event->isValid) {
            return '';
        }

        $template = $event->template;
        $variables = $event->variables;
        $templateMode = $event->templateMode;

        Log::debug("Rendering template: $template", [__METHOD__]);

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set($templateMode);

        // Render and return
        $renderingTemplate = $this->renderingTemplate;
        $this->renderingTemplate = $template;

        try {
            $output = $this->twig->get()->render($template, $variables);
        } finally {
            $this->renderingTemplate = $renderingTemplate;

            TemplateMode::set($oldTemplateMode);
        }

        event($event = new TemplateRendered($template, $variables, $templateMode, $output));

        return $event->output;
    }

    /**
     * Renders a template in sandbox mode.
     */
    public function renderSandboxedTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
    ): string {
        return $this->sandbox(fn () => $this->renderTemplate($template, $variables, $templateMode), $templateMode);
    }

    /**
     * Renders a page template (with beginPage/endPage lifecycle).
     *
     * Delegates output buffering, BeginPage/EndPage events, and placeholder
     * replacement to {@see PageLifecycle}, keeping template rendering concerns
     * separate from page structure and asset injection.
     */
    public function renderPageTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
    ): string {
        $templateMode ??= TemplateMode::get();

        event($event = new RenderingPageTemplate($template, $variables, $templateMode));

        if (! $event->isValid) {
            return '';
        }

        $template = $event->template;
        $variables = $event->variables;
        $templateMode = $event->templateMode;

        $isRenderingPageTemplate = $this->isRenderingPageTemplate;
        $this->isRenderingPageTemplate = true;

        try {
            $output = $this->pageLifecycle->wrap(
                fn () => $this->renderTemplate($template, $variables, $templateMode),
            );
        } finally {
            $this->isRenderingPageTemplate = $isRenderingPageTemplate;
        }

        event($event = new PageTemplateRendered($template, $variables, $templateMode, $output));

        return $event->output;
    }

    public function isRenderingPageTemplate(): bool
    {
        return $this->isRenderingPageTemplate;
    }

    /**
     * Renders an inline template string.
     */
    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $escapeHtml = false,
    ): string {
        // If there are no dynamic tags, just return the template
        if (! str_contains($template, '{')) {
            return $template;
        }

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set($templateMode);

        $twig = $this->twig->get();

        if (! $escapeHtml) {
            $twig->setDefaultEscaperStrategy(false);
        }

        $lastRenderingTemplate = $this->renderingTemplate;
        $this->renderingTemplate = 'string:'.$template;

        try {
            return $twig->createTemplate($template)->render($variables);
        } finally {
            $this->renderingTemplate = $lastRenderingTemplate;

            if (! $escapeHtml) {
                $twig->setDefaultEscaperStrategy();
            }

            TemplateMode::set($oldTemplateMode);
        }
    }

    /**
     * Renders a template defined by a string in a sandboxed environment.
     *
     * @see renderString()
     */
    public function renderSandboxedString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $escapeHtml = false,
    ): string {
        return $this->sandbox(fn () => $this->renderString($template, $variables, $templateMode, $escapeHtml), $templateMode);
    }

    /**
     * Renders an object template.
     *
     * The passed-in `$object` will be available to the template as an `object` variable.
     *
     * The template will be parsed for “property tags” (e.g. `{foo}`), which will get replaced with
     * full Twig output tags (e.g. `{{ object.foo|raw }}`.
     *
     * If `$object` is an instance of [[Arrayable]], any attributes returned by its [[Arrayable::fields()|fields()]] or
     * [[Arrayable::extraFields()|extraFields()]] methods will also be available as variables to the template.
     */
    public function renderObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        // If there are no dynamic tags, just return the template
        if (! str_contains($template, '{')) {
            return trim($template);
        }

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set($templateMode);
        $twig = $this->twig->get();

        // Temporarily disable strict variables if it's enabled
        $strictVariables = $twig->isStrictVariables();

        if ($strictVariables) {
            $twig->disableStrictVariables();
        }

        $twig->setDefaultEscaperStrategy(false);
        $lastRenderingTemplate = $this->renderingTemplate;
        $this->renderingTemplate = 'string:'.$template;

        try {
            // Is this the first time we've parsed this template?
            $cacheKey = md5($templateMode->value.':'.$template);

            if (! isset($this->objectTemplates[$cacheKey])) {
                // Replace shortcut "{var}"s with "{{object.var}}"s, without affecting normal Twig tags
                $template = $this->normalizeObjectTemplate($template);
                $this->objectTemplates[$cacheKey] = $twig->createTemplate($template);
            }

            // Get the variables to pass to the template
            if ($object instanceof ArrayableInterface) {
                if (preg_match('/\binclude\b/', $template)) {
                    // Export all normal fields, since we don’t know what the included template is going to need
                    // (https://github.com/craftcms/cms/issues/18165)
                    $fields = [];
                } else {
                    $fields = $this->filterFieldsByTemplate($object->fields(), $template) ?: ['!'];
                }

                $variables += $object->toArray(
                    fields: $fields,
                    expand: $this->filterFieldsByTemplate($object->extraFields(), $template),
                    recursive: false,
                );
            } elseif (is_object($object) && ($object instanceof Arrayable || method_exists($object, 'toArray'))) {
                $variables += $object->toArray();
            }

            $variables['object'] = $object;
            $variables['_variables'] = $variables;

            /** @var Template $templateObj */
            $templateObj = $this->objectTemplates[$cacheKey];

            return trim($templateObj->render($variables));
        } finally {
            $this->renderingTemplate = $lastRenderingTemplate;
            $twig->setDefaultEscaperStrategy();
            TemplateMode::set($oldTemplateMode);

            // Re-enable strict variables
            if ($strictVariables) {
                $twig->enableStrictVariables();
            }
        }
    }

    /**
     * Renders an object template in a sandboxed environment.
     *
     * @see renderObjectTemplate()
     */
    public function renderSandboxedObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        return $this->sandbox(fn () => $this->renderObjectTemplate($template, $object, $variables, $templateMode), $templateMode);
    }

    /**
     * Normalizes {property} shorthand into {{ object.property|raw }}.
     */
    public function normalizeObjectTemplate(string $template): string
    {
        $tokens = [];
        $tokenize = function (string $value) use (&$tokens): string {
            $token = 'tok_'.Str::random(10);
            $tokens[$token] = $value;

            return $token;
        };

        // Tokenize {% verbatim %} ... {% endverbatim %} tags in their entirety
        $template = preg_replace_callback('/\{%-?\s*verbatim\s*-?%\}.*?{%-?\s*endverbatim\s*-?%\}/s',
            fn (array $matches): string => $tokenize($matches[0]),
            $template
        );

        // Tokenize any remaining Twig tags (including print tags)
        $template = preg_replace_callback('/\{%-?\s*\w+.*?%\}|(?<!\{)\{\{(?!\{).+?(?<!\})\}\}(?!\})/s',
            fn (array $matches): string => $tokenize($matches[0]),
            (string) $template
        );

        // Tokenize inline code and code blocks
        $template = preg_replace_callback(
            '/(?<!`)(`|`{3,})(?!`).*?(?<!`)\1(?!`)/s',
            fn (array $matches): string => $tokenize('{% verbatim %}'.$matches[0].'{% endverbatim %}'),
            (string) $template
        );

        // Tokenize objects (multiple passes for nested objects)
        do {
            $template = preg_replace_callback(
                '/\{\s*([\'"]?)\w+\1\s*:[^\{]+?\}/',
                fn (array $matches): string => $tokenize($matches[0]),
                (string) $template,
                -1,
                $count
            );
        } while ($count > 0);

        // Swap out the remaining {xyz} tags with {{object.xyz}}
        $template = preg_replace_callback('/(?<!\{)\{\s*(\w+)([^\{]*?)\}/', function (array $match) {
            // Is this a function call like `clone()`?
            if (! empty($match[2]) && $match[2][0] === '(') {
                $replace = $match[1].$match[2];
            } else {
                $replace = "(_variables.$match[1] ?? object.$match[1])$match[2]";
            }

            return "{{ $replace|raw }}";
        }, (string) $template);

        // Restore tokenized content (sequential to handle nested tokens)
        foreach (array_reverse($tokens) as $token => $value) {
            $template = str_replace($token, $value, $template);
        }

        return $template;
    }

    /**
     * Enables sandbox, runs callback, disables sandbox.
     */
    private function sandbox(callable $callback, ?TemplateMode $templateMode): string
    {
        if (! $this->generalConfig->enableTwigSandbox) {
            return $callback();
        }

        $extension = $this->twig->get($templateMode)->getExtension(SandboxExtension::class);

        if ($extension->isSandboxed()) {
            return $callback();
        }

        $extension->enableSandbox();

        try {
            return $callback();
        } finally {
            $extension->disableSandbox();
        }
    }

    /**
     * Filters fields array to only those referenced in template.
     */
    private function filterFieldsByTemplate(array $fields, string $template): array
    {
        $filtered = [];

        foreach ($fields as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            if (preg_match(sprintf('/\b%s\b/', preg_quote((string) $field, '/')), $template)) {
                $filtered[] = $field;
            }
        }

        return $filtered;
    }
}
