<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/disclosure-toggle */
class __TwigTemplate_8c7c12f590803c759001e9252da74d71 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_includes/disclosure-toggle');
        // line 1
        $context['attributes'] ??= [];
        // line 2
        $context['controls'] ??= null;
        // line 3
        $context['expanded'] ??= 'true';
        // line 4
        $context['content'] = Twig\Extension\CoreExtension::trim((($context['content']) ?? ((($this->unwrap()->hasBlock('content', $context, $blocks)) ? ($this->unwrap()->renderBlock('content', $context, $blocks)) : ('')))));
        // line 5
        yield '
';
        // line 6
        if ((isset($context['controls']) || array_key_exists('controls', $context) ? $context['controls'] : (function () {
            throw new RuntimeError('Variable "controls" does not exist.', 6, $this->source);
        })())) {
            // line 7
            yield '    <craft-disclosure id="';
            (((array_key_exists('id', $context) && ! (null === (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 7, $this->source);
            })())))) ? (yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 7, $this->source);
            })()), 'html', null, true)) : (yield null));
            yield '">
        <button
            type="button"
            aria-controls="';
            // line 10
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['controls']) || array_key_exists('controls', $context) ? $context['controls'] : (function () {
                throw new RuntimeError('Variable "controls" does not exist.', 10, $this->source);
            })()), 'html', null, true);
            yield '"
            aria-expanded="';
            // line 11
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['expanded']) || array_key_exists('expanded', $context) ? $context['expanded'] : (function () {
                throw new RuntimeError('Variable "expanded" does not exist.', 11, $this->source);
            })()), 'html', null, true);
            yield '"
            ';
            // line 12
            yield craft\helpers\Html::renderTagAttributes((isset($context['attributes']) || array_key_exists('attributes', $context) ? $context['attributes'] : (function () {
                throw new RuntimeError('Variable "attributes" does not exist.', 12, $this->source);
            })()));
            yield '
        >
            ';
            // line 14
            yield isset($context['content']) || array_key_exists('content', $context) ? $context['content'] : (function () {
                throw new RuntimeError('Variable "content" does not exist.', 14, $this->source);
            })();
            yield '
        </button>
    </craft-disclosure>
';
        }
        craft\helpers\Template::endProfile('template', '_includes/disclosure-toggle');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/disclosure-toggle';
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
        return [76 => 14,  71 => 12,  67 => 11,  63 => 10,  56 => 7,  54 => 6,  51 => 5,  49 => 4,  47 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% set attributes = attributes ?? {} %}
{% set controls = controls ?? null %}
{% set expanded = expanded ?? 'true' %}
{% set content = (content ?? block('content') ?? '')|trim %}

{% if controls %}
    <craft-disclosure id=\"{{ id ?? null }}\">
        <button
            type=\"button\"
            aria-controls=\"{{ controls }}\"
            aria-expanded=\"{{ expanded }}\"
            {{ attr(attributes) }}
        >
            {{ content | raw }}
        </button>
    </craft-disclosure>
{% endif %}
", '_includes/disclosure-toggle', '/tmp/packages/craft5/src/templates/_includes/disclosure-toggle.twig');
    }
}
