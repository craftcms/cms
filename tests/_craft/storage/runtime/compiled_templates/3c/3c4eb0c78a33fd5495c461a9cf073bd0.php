<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/checkbox */
class __TwigTemplate_f686c957f4c47f75f99528faf81bd808 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/checkbox');
        // line 1
        $___internal_parse_1_ = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $blocks) {
            // line 2
            yield '
';
            // line 3
            $context['id'] ??= 'checkbox'.Twig\Extension\CoreExtension::random($this->env->getCharset());
            // line 4
            $context['label'] = (($context['checkboxLabel']) ?? ((($context['label']) ?? (null))));
            // line 5
            yield '
';
            // line 6
            $context['aria'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((((craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', true, true, false, 6) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, false, false, 6) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, false, false, 6)) : ([])), (($context['aria']) ?? ([])));
            // line 7
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' =>             // line 8
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 8, $this->source);
})()), 'class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass(((            // line 9
    $context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [((((            // line 10
        $context['targetPrefix']) ?? ((($context['toggle']) ?? ((($context['reverseToggle']) ?? (false))))))) ? ('fieldtoggle') : (null)), 'checkbox'])), 'checked' => (((            // line 13
            $context['checked']) ?? (false)) && (isset($context['checked']) || array_key_exists('checked', $context) ? $context['checked'] : (function () {
                throw new RuntimeError('Variable "checked" does not exist.', 13, $this->source);
            })())), 'autofocus' => (((            // line 14
                $context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 14, $this->source);
                })()), 'app', [], 'any', false, false, false, 14), 'request', [], 'any', false, false, false, 14), 'isMobileBrowser', [true], 'method', false, false, false, 14)), 'disabled' => ((((            // line 15
                    $context['disabled']) ?? (false))) ? (true) : (false)), 'aria' => $this->extensions['craft\web\twig\Extension']->mergeFilter(            // line 16
                        (isset($context['aria']) || array_key_exists('aria', $context) ? $context['aria'] : (function () {
                            throw new RuntimeError('Variable "aria" does not exist.', 16, $this->source);
                        })()), ['labelledby' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 17
                            ($context['aria'] ?? null), 'label', [], 'any', true, true, false, 17) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'label', [], 'any', false, false, false, 17) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'label', [], 'any', false, false, false, 17)) : (false))) ? (false) : ((($context['labelledBy']) ?? (false)))), 'describedby' => ((            // line 18
                                $context['describedBy']) ?? ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'describedby', [], 'any', true, true, false, 18) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'describedby', [], 'any', false, false, false, 18) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'describedby', [], 'any', false, false, false, 18)) : (false))))]), 'data' => $this->extensions['craft\web\twig\Extension']->mergeFilter(((            // line 20
                                    $context['data']) ?? ([])), ['target-prefix' => ((            // line 21
                                        $context['targetPrefix']) ?? (false)), 'target' => ((            // line 22
                                            $context['toggle']) ?? (false)), 'reverse-target' => ((            // line 23
                                                $context['reverseToggle']) ?? (false))])], ((            // line 25
                                                    $context['inputAttributes']) ?? ([])), true);
            // line 26
            yield '
';
            // line 27
            if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
                // line 28
                $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                    throw new RuntimeError('Variable "inputAttributes" does not exist.', 28, $this->source);
                })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
            }
            // line 30
            yield '
';
            // line 31
            if ((array_key_exists('name', $context) && (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 31, $this->source);
            })())) < 3) || (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 31, $this->source);
            })()), -2) != '[]')))) {
                // line 32
                yield '    ';
                yield craft\helpers\Html::hiddenInput((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                    throw new RuntimeError('Variable "name" does not exist.', 32, $this->source);
                })()), '');
                yield '
';
            }
            // line 34
            yield '
';
            // line 35
            yield craft\helpers\Html::input('checkbox', (($context['name']) ?? (null)), (($context['value']) ?? (1)), (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 35, $this->source);
            })()));
            yield '

<label for="';
            // line 37
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 37, $this->source);
            })()), 'html', null, true);
            yield '">
    ';
            // line 38
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                throw new RuntimeError('Variable "label" does not exist.', 38, $this->source);
            })()), 'html', null, true);
            yield '
    ';
            // line 39
            if ((($context['info']) ?? (null))) {
                // line 40
                yield '        <span class="info">';
                yield $this->extensions['craft\web\twig\Extension']->markdownFilter((isset($context['info']) || array_key_exists('info', $context) ? $context['info'] : (function () {
                    throw new RuntimeError('Variable "info" does not exist.', 40, $this->source);
                })()));
                yield '</span>
    ';
            }
            // line 42
            yield '</label>

';
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        yield Twig\Extension\CoreExtension::spaceless($___internal_parse_1_);
        craft\helpers\Template::endProfile('template', '_includes/forms/checkbox');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/checkbox';
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
        return [122 => 1,  116 => 42,  110 => 40,  108 => 39,  104 => 38,  100 => 37,  95 => 35,  92 => 34,  86 => 32,  84 => 31,  81 => 30,  78 => 28,  76 => 27,  73 => 26,  71 => 25,  70 => 23,  69 => 22,  68 => 21,  67 => 20,  66 => 18,  65 => 17,  64 => 16,  63 => 15,  62 => 14,  61 => 13,  60 => 10,  59 => 9,  58 => 8,  57 => 7,  55 => 6,  52 => 5,  50 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{%- apply spaceless %}

{% set id = id ?? \"checkbox#{random()}\" %}
{% set label = checkboxLabel ?? label ?? null %}

{% set aria = (inputAttributes.aria ?? {})|merge(aria ?? {}) %}
{% set inputAttributes = {
    id: id,
    class: (class ?? [])|explodeClass|merge([
        (targetPrefix ?? toggle ?? reverseToggle ?? false) ? 'fieldtoggle' : null,
        'checkbox'
    ]|filter),
    checked: (checked ?? false) and checked,
    autofocus: (autofocus ?? false) and not craft.app.request.isMobileBrowser(true),
    disabled: (disabled ?? false) ? true : false,
    aria: aria|merge({
        labelledby: (aria.label ?? false) ? false : (labelledBy ?? false),
        describedby: describedBy ?? aria.describedby ?? false,
    }),
    data: (data ?? {})|merge({
        'target-prefix': targetPrefix ?? false,
        target: toggle ?? false,
        'reverse-target': reverseToggle ?? false,
    }),
}|merge(inputAttributes ?? [], recursive=true) %}

{% if block('attr') is defined %}
    {%- set inputAttributes = inputAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{% endif %}

{% if name is defined and (name|length < 3 or name|slice(-2) != '[]') %}
    {{ hiddenInput(name, '') }}
{% endif %}

{{ input('checkbox', name ?? null, value ?? 1, inputAttributes) }}

<label for=\"{{ id }}\">
    {{ label }}
    {% if info ?? null %}
        <span class=\"info\">{{ info|md|raw }}</span>
    {% endif %}
</label>

{% endapply -%}
", '_includes/forms/checkbox', '/tmp/packages/craft5/src/templates/_includes/forms/checkbox.twig');
    }
}
