<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/links */
class __TwigTemplate_078f7d5da7b6a74bafa6f0919cdb37f5 extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_includes/links');
        // line 8
        echo '
';
        craft\helpers\Template::endProfile('template', '_includes/links');
    }

    // line 1
    public function macro_externalLinkIcon(...$__varargs__)
    {
        $this->env->mergeGlobals([
            'varargs' => $__varargs__,
        ]);
        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'externalLinkIcon');
            // line 2
            echo '    ';
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['data-icon' => 'external', 'role' => 'img', 'aria-label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Opens in a new window', 'app')]);
            // line 6
            echo '
';
            craft\helpers\Template::endProfile('macro', 'externalLinkIcon');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 9
    public function macro_externalLink($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'externalLink');
            // line 10
            echo '    ';
            $context['linkAttributes'] = ['href' => craft\helpers\Template::attribute($this->env, $this->source,             // line 11
                (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 11, $this->source);
                })()), 'link', []), 'target' => '_blank', 'rel' => 'noopener', 'html' => ((((craft\helpers\Template::attribute($this->env, $this->source,             // line 14
                    ($context['config'] ?? null), 'html', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'html', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'html', [])) : (twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                        throw new RuntimeError('Variable "config" does not exist.', 14, $this->source);
                    })()), 'text', [])))).twig_call_macro($macros['_self'], 'macro_externalLinkIcon', [], 14, $context, $this->getSourceContext()))];
            // line 16
            echo '    ';
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', (isset($context['linkAttributes']) || array_key_exists('linkAttributes', $context) ? $context['linkAttributes'] : (function () {
                throw new RuntimeError('Variable "linkAttributes" does not exist.', 16, $this->source);
            })()));
            echo '
';
            craft\helpers\Template::endProfile('macro', 'externalLink');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_includes/links';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [91 => 16,  89 => 14,  88 => 11,  86 => 10,  72 => 9,  61 => 6,  58 => 2,  45 => 1,  39 => 8];
    }

    public function getSourceContext()
    {
        return new Source("{% macro externalLinkIcon() %}
    {{ tag('span', {
        'data-icon': 'external',
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
", '_includes/links', '/Users/brianhanson/Development/craft5/src/templates/_includes/links.twig');
    }
}
