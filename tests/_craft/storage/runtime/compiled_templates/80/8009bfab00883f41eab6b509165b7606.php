<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/links */
class __TwigTemplate_b6b5a433645ffc6bed667796d6b0f53d extends Template
{
    private readonly Source $source;

    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_includes/links');
        // line 9
        yield '
';
        craft\helpers\Template::endProfile('template', '_includes/links');
        yield from [];
    }

    // line 1
    public function macro_externalLinkIcon(...$__varargs__)
    {
        $this->env->getGlobals();
        [
            'varargs' => $__varargs__,
        ];

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () {
            craft\helpers\Template::beginProfile('macro', 'externalLinkIcon');
            // line 2
            yield '    ';
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['data-icon' => 'external', 'data-icon-size' => 'puny', 'role' => 'img', 'aria-label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Opens in a new window', 'app')]);
            // line 7
            yield '
';
            craft\helpers\Template::endProfile('macro', 'externalLinkIcon');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 10
    public function macro_externalLink($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'externalLink');
            // line 11
            yield '    ';
            $context['linkAttributes'] = ['href' => craft\helpers\Template::attribute($this->env, $this->source,             // line 12
                (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 12, $this->source);
                })()), 'link', [], 'any', false, false, false, 12), 'target' => '_blank', 'rel' => 'noopener', 'html' => ((((craft\helpers\Template::attribute($this->env, $this->source,             // line 15
                    ($context['config'] ?? null), 'html', [], 'any', true, true, false, 15) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'html', [], 'any', false, false, false, 15) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'html', [], 'any', false, false, false, 15)) : ($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                        throw new RuntimeError('Variable "config" does not exist.', 15, $this->source);
                    })()), 'text', [], 'any', false, false, false, 15)))).CoreExtension::callMacro($macros['_self'], 'macro_externalLinkIcon', [], 15, $context, $this->getSourceContext()))];
            // line 17
            yield '    ';
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', (isset($context['linkAttributes']) || array_key_exists('linkAttributes', $context) ? $context['linkAttributes'] : (function () {
                throw new RuntimeError('Variable "linkAttributes" does not exist.', 17, $this->source);
            })()));
            yield '
';
            craft\helpers\Template::endProfile('macro', 'externalLink');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/links';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return [92 => 17,  90 => 15,  89 => 12,  87 => 11,  74 => 10,  66 => 7,  63 => 2,  51 => 1,  44 => 9];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% macro externalLinkIcon() %}
    {{ tag('span', {
        'data-icon': 'external',
        'data-icon-size': 'puny',
        'role': 'img',
        'aria-label': 'Opens in a new window'|t('app'),
    }) }}
{% endmacro %}

{% macro externalLink(config) %}
    {% set linkAttributes = {
        href: config.link,
        target: '_blank',
        rel: 'noopener',
        html: (config.html ?? config.text|e) ~ _self.externalLinkIcon()
    } %}
    {{ tag('a', linkAttributes) }}
{% endmacro %}
", '_includes/links', '/tmp/packages/craft5/src/templates/_includes/links.twig');
    }
}
