<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/color.twig */
class __TwigTemplate_9ef678c7b214d0a199b2159292184b58 extends Template
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
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_includes/forms/color.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_includes/forms/color.twig', 1)->unwrap();
        // line 3
        $context['id'] ??= 'color'.twig_random($this->env);
        // line 4
        $context['containerId'] = ((($context['containerId']) ?? ((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 4, $this->source);
        })()))).'-container');
        // line 5
        $context['hexLabelId'] = ('hex-'.(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 5, $this->source);
        })()));
        // line 6
        $context['name'] ??= null;
        // line 7
        $context['value'] ??= null;
        // line 8
        $context['small'] ??= false;
        // line 9
        $context['autofocus'] = ((($context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 9, $this->source);
        })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method'));
        // line 10
        $context['disabled'] ??= false;
        // line 11
        $context['labelledBy'] ??= null;
        // line 12
        echo '
';
        // line 13
        $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' =>         // line 14
(isset($context['containerId']) || array_key_exists('containerId', $context) ? $context['containerId'] : (function () {
    throw new RuntimeError('Variable "containerId" does not exist.', 14, $this->source);
})()), 'class' => [0 => 'flex', 1 => 'flex-nowrap', 2 => 'color-container'], ], ((        // line 16
    $context['containerAttributes']) ?? ([])), true);
        // line 18
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 19
            $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 19, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 21
        echo '
';
        // line 22
        ob_start();
        // line 23
        echo '    ';
        ob_start();
        // line 24
        echo '        ';
        ob_start();
        // line 27
        echo '            ';
        echo $this->extensions['craft\web\twig\Extension']->tagFunction('div', ['class' => [0 => 'color-preview'], 'style' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['background-color' =>         // line 29
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 29, $this->source);
})()), ])]);
        // line 30
        echo '
        ';
        echo craft\helpers\Html::tag('div', ob_get_clean(), ['class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'color', 1 => 'static', 2 => ((        // line 25
            (isset($context['small']) || array_key_exists('small', $context) ? $context['small'] : (function () {
                throw new RuntimeError('Variable "small" does not exist.', 25, $this->source);
            })())) ? ('small') : (''))])]);
        // line 32
        echo '        <div class="color-input-container">
            <div class="color-hex-indicator light code" aria-hidden="true">#</div>
            <span id="';
        // line 34
        echo twig_escape_filter($this->env, (isset($context['hexLabelId']) || array_key_exists('hexLabelId', $context) ? $context['hexLabelId'] : (function () {
            throw new RuntimeError('Variable "hexLabelId" does not exist.', 34, $this->source);
        })()), 'html', null, true);
        echo '" class="visually-hidden">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Color hex value', 'app'), 'html', null, true);
        echo '</span>
            ';
        // line 35
        echo twig_call_macro($macros['forms'], 'macro_text', [['id' =>         // line 36
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 36, $this->source);
})()), 'describedBy' => ((        // line 37
    $context['describedBy']) ?? (false)), 'name' =>         // line 38
(isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
    throw new RuntimeError('Variable "name" does not exist.', 38, $this->source);
})()), 'value' => twig_trim_filter(        // line 39
    (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
        throw new RuntimeError('Variable "value" does not exist.', 39, $this->source);
    })()), '#'), 'size' => 10, 'class' => 'color-input', 'autofocus' =>         // line 42
(isset($context['autofocus']) || array_key_exists('autofocus', $context) ? $context['autofocus'] : (function () {
    throw new RuntimeError('Variable "autofocus" does not exist.', 42, $this->source);
})()), 'disabled' =>         // line 43
(isset($context['disabled']) || array_key_exists('disabled', $context) ? $context['disabled'] : (function () {
    throw new RuntimeError('Variable "disabled" does not exist.', 43, $this->source);
})()), 'labelledBy' => twig_join_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 =>         // line 44
(isset($context['labelledBy']) || array_key_exists('labelledBy', $context) ? $context['labelledBy'] : (function () {
    throw new RuntimeError('Variable "labelledBy" does not exist.', 44, $this->source);
})()), 1 => (isset($context['hexLabelId']) || array_key_exists('hexLabelId', $context) ? $context['hexLabelId'] : (function () {
    throw new RuntimeError('Variable "hexLabelId" does not exist.', 44, $this->source);
})()), ]), ' '), ]], 35, $context, $this->getSourceContext());
        // line 45
        echo '
        </div>
    ';
        echo craft\helpers\Html::tag('div', ob_get_clean(),         // line 23
            (isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 23, $this->source);
            })()));
        $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 22
        echo twig_spaceless($___internal_parse_0_);
        // line 50
        ob_start();
        // line 51
        echo "    new Craft.ColorInput('#";
        echo twig_escape_filter($this->env, $this->env->getFilter('namespaceInputId')->getCallable()((isset($context['containerId']) || array_key_exists('containerId', $context) ? $context['containerId'] : (function () {
            throw new RuntimeError('Variable "containerId" does not exist.', 51, $this->source);
        })())), 'html', null, true);
        echo "');
";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        craft\helpers\Template::endProfile('template', '_includes/forms/color.twig');
    }

    public function getTemplateName()
    {
        return '_includes/forms/color.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [120 => 51,  118 => 50,  116 => 22,  113 => 23,  109 => 45,  107 => 44,  106 => 43,  105 => 42,  104 => 39,  103 => 38,  102 => 37,  101 => 36,  100 => 35,  94 => 34,  90 => 32,  88 => 25,  85 => 30,  83 => 29,  81 => 27,  78 => 24,  75 => 23,  73 => 22,  70 => 21,  67 => 19,  65 => 18,  63 => 16,  62 => 14,  61 => 13,  58 => 12,  56 => 11,  54 => 10,  52 => 9,  50 => 8,  48 => 7,  46 => 6,  44 => 5,  42 => 4,  40 => 3,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import \"_includes/forms\" as forms -%}

{% set id = id ?? \"color#{random()}\" -%}
{% set containerId = containerId ?? id~'-container' -%}
{% set hexLabelId = \"hex-#{id}\" %}
{% set name = name ?? null -%}
{% set value = value ?? null -%}
{% set small = small ?? false -%}
{% set autofocus = (autofocus ?? false) and not craft.app.request.isMobileBrowser(true) -%}
{% set disabled = disabled ?? false -%}
{% set labelledBy = labelledBy ?? null %}

{% set containerAttributes = {
    id: containerId,
    class: ['flex', 'flex-nowrap', 'color-container'],
}|merge(containerAttributes ?? [], recursive=true) %}

{%- if block('attr') is defined %}
    {%- set containerAttributes = containerAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{% endif %}

{% apply spaceless %}
    {% tag 'div' with containerAttributes %}
        {% tag 'div' with {
            class: ['color', 'static', small ? 'small']|filter,
        } %}
            {{ tag('div', {
                class: ['color-preview'],
                style: {'background-color': value}|filter,
            }) }}
        {% endtag %}
        <div class=\"color-input-container\">
            <div class=\"color-hex-indicator light code\" aria-hidden=\"true\">#</div>
            <span id=\"{{ hexLabelId }}\" class=\"visually-hidden\">{{ 'Color hex value'|t('app') }}</span>
            {{ forms.text({
                id: id,
                describedBy: describedBy ?? false,
                name: name,
                value: value|trim('#'),
                size: 10,
                class: 'color-input',
                autofocus: autofocus,
                disabled: disabled,
                labelledBy: [labelledBy, hexLabelId]|filter|join(' '),
            }) }}
        </div>
    {% endtag %}
{% endapply -%}

{% js %}
    new Craft.ColorInput('#{{ containerId|namespaceInputId }}');
{% endjs -%}
", '_includes/forms/color.twig', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/color.twig');
    }
}
