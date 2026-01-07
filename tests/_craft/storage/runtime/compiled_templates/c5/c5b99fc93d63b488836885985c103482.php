<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/checkbox */
class __TwigTemplate_1d26a164d6a62e6c5c0af8980ab53071 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_includes/forms/checkbox');
        // line 1
        ob_start();
        // line 2
        echo '
';
        // line 3
        $context['id'] ??= 'checkbox'.twig_random($this->env);
        // line 4
        $context['label'] = (($context['checkboxLabel']) ?? ((($context['label']) ?? (null))));
        // line 5
        echo '
';
        // line 6
        $context['aria'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((((craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [])) : ([])), (($context['aria']) ?? ([])));
        // line 7
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' =>         // line 8
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 8, $this->source);
})()), 'class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass(((        // line 9
    $context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => ((((        // line 10
        $context['toggle']) ?? ((($context['reverseToggle']) ?? (false))))) ? ('fieldtoggle') : (null)), 1 => 'checkbox'])), 'checked' => (((        // line 13
            $context['checked']) ?? (false)) && (isset($context['checked']) || array_key_exists('checked', $context) ? $context['checked'] : (function () {
                throw new RuntimeError('Variable "checked" does not exist.', 13, $this->source);
            })())), 'autofocus' => (((        // line 14
                $context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 14, $this->source);
                })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method')), 'disabled' => ((((        // line 15
                    $context['disabled']) ?? (false))) ? (true) : (false)), 'aria' => $this->extensions['craft\web\twig\Extension']->mergeFilter(        // line 16
                        (isset($context['aria']) || array_key_exists('aria', $context) ? $context['aria'] : (function () {
                            throw new RuntimeError('Variable "aria" does not exist.', 16, $this->source);
                        })()), ['labelledby' => (((((craft\helpers\Template::attribute($this->env, $this->source,         // line 17
                            ($context['aria'] ?? null), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'label', [])) : (false))) ? (false) : ((($context['labelledBy']) ?? (false)))), 'describedby' => ((        // line 18
                                $context['describedBy']) ?? ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'describedby', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'describedby', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['aria'] ?? null), 'describedby', [])) : (false))))]), 'data' => $this->extensions['craft\web\twig\Extension']->mergeFilter(((        // line 20
                                    $context['data']) ?? ([])), ['target' => ((        // line 21
                                        $context['toggle']) ?? (false)), 'reverse-target' => ((        // line 22
                                            $context['reverseToggle']) ?? (false))]), ], ((        // line 24
                                                $context['inputAttributes']) ?? ([])), true);
        // line 25
        echo '
';
        // line 26
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 27
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 27, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 29
        echo '
';
        // line 30
        if ((array_key_exists('name', $context) && (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
            throw new RuntimeError('Variable "name" does not exist.', 30, $this->source);
        })())) < 3) || (twig_slice($this->env, (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
            throw new RuntimeError('Variable "name" does not exist.', 30, $this->source);
        })()), -2) != '[]')))) {
            // line 31
            echo '    ';
            echo craft\helpers\Html::hiddenInput((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 31, $this->source);
            })()), '');
            echo '
';
        }
        // line 33
        echo '
';
        // line 34
        echo craft\helpers\Html::input('checkbox', (($context['name']) ?? (null)), (($context['value']) ?? (1)), (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
            throw new RuntimeError('Variable "inputAttributes" does not exist.', 34, $this->source);
        })()));
        echo '

<label for="';
        // line 36
        echo twig_escape_filter($this->env, (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 36, $this->source);
        })()), 'html', null, true);
        echo '">
    ';
        // line 37
        echo twig_escape_filter($this->env, (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
            throw new RuntimeError('Variable "label" does not exist.', 37, $this->source);
        })()), 'html', null, true);
        echo '
    ';
        // line 38
        if ((($context['info']) ?? (null))) {
            // line 39
            echo '        <span class="info">';
            echo $this->extensions['craft\web\twig\Extension']->markdownFilter((isset($context['info']) || array_key_exists('info', $context) ? $context['info'] : (function () {
                throw new RuntimeError('Variable "info" does not exist.', 39, $this->source);
            })()));
            echo '</span>
    ';
        }
        // line 41
        echo '</label>

';
        $___internal_parse_2_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        echo twig_spaceless($___internal_parse_2_);
        craft\helpers\Template::endProfile('template', '_includes/forms/checkbox');
    }

    public function getTemplateName()
    {
        return '_includes/forms/checkbox';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [115 => 1,  110 => 41,  104 => 39,  102 => 38,  98 => 37,  94 => 36,  89 => 34,  86 => 33,  80 => 31,  78 => 30,  75 => 29,  72 => 27,  70 => 26,  67 => 25,  65 => 24,  64 => 22,  63 => 21,  62 => 20,  61 => 18,  60 => 17,  59 => 16,  58 => 15,  57 => 14,  56 => 13,  55 => 10,  54 => 9,  53 => 8,  52 => 7,  50 => 6,  47 => 5,  45 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{%- apply spaceless %}

{% set id = id ?? \"checkbox#{random()}\" %}
{% set label = checkboxLabel ?? label ?? null %}

{% set aria = (inputAttributes.aria ?? {})|merge(aria ?? {}) %}
{% set inputAttributes = {
    id: id,
    class: (class ?? [])|explodeClass|merge([
        (toggle ?? reverseToggle ?? false) ? 'fieldtoggle' : null,
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
", '_includes/forms/checkbox', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/checkbox.twig');
    }
}
