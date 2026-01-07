<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/color.twig */
class __TwigTemplate_216aabae92daedf91037085494ed9dcf extends Template
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
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_includes/forms/color.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_includes/forms/color.twig', 1)->unwrap();
        // line 3
        $context['id'] ??= 'color'.Twig\Extension\CoreExtension::random($this->env->getCharset());
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
        })()), 'app', [], 'any', false, false, false, 9), 'request', [], 'any', false, false, false, 9), 'isMobileBrowser', [true], 'method', false, false, false, 9));
        // line 10
        $context['disabled'] ??= false;
        // line 11
        $context['labelledBy'] ??= null;
        // line 12
        yield '
';
        // line 13
        $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' =>         // line 14
(isset($context['containerId']) || array_key_exists('containerId', $context) ? $context['containerId'] : (function () {
    throw new RuntimeError('Variable "containerId" does not exist.', 14, $this->source);
})()), 'class' => ['flex', 'flex-nowrap', 'color-container']], ((        // line 16
    $context['containerAttributes']) ?? ([])), true);
        // line 18
        if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
            // line 19
            $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 19, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 21
        yield '
';
        // line 22
        $___internal_parse_0_ = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            // line 23
            yield '    ';
            ob_start();
            // line 24
            yield '        ';
            ob_start();
            // line 27
            yield '            ';
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('div', ['class' => ['color-preview'], 'style' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['background-color' =>             // line 29
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 29, $this->source);
})())])]);
            // line 30
            yield '
        ';
            echo craft\helpers\Html::tag('div', ob_get_clean(), ['class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['color', 'static', ((            // line 25
                (isset($context['small']) || array_key_exists('small', $context) ? $context['small'] : (function () {
                    throw new RuntimeError('Variable "small" does not exist.', 25, $this->source);
                })())) ? ('small') : (''))])]);
            // line 32
            yield '        <div class="color-input-container">
            <div class="color-hex-indicator light code" aria-hidden="true">#</div>
            <span id="';
            // line 34
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['hexLabelId']) || array_key_exists('hexLabelId', $context) ? $context['hexLabelId'] : (function () {
                throw new RuntimeError('Variable "hexLabelId" does not exist.', 34, $this->source);
            })()), 'html', null, true);
            yield '" class="visually-hidden">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Color hex value', 'app'), 'html', null, true);
            yield '</span>
            ';
            // line 35
            yield CoreExtension::callMacro($macros['forms'], 'macro_text', [['id' =>             // line 36
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 36, $this->source);
})()), 'describedBy' => ((            // line 37
    $context['describedBy']) ?? (false)), 'name' =>             // line 38
(isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
    throw new RuntimeError('Variable "name" does not exist.', 38, $this->source);
})()), 'value' => Twig\Extension\CoreExtension::trim(            // line 39
    (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
        throw new RuntimeError('Variable "value" does not exist.', 39, $this->source);
    })()), '#'), 'size' => 10, 'class' => 'color-input', 'autofocus' =>             // line 42
(isset($context['autofocus']) || array_key_exists('autofocus', $context) ? $context['autofocus'] : (function () {
    throw new RuntimeError('Variable "autofocus" does not exist.', 42, $this->source);
})()), 'disabled' =>             // line 43
(isset($context['disabled']) || array_key_exists('disabled', $context) ? $context['disabled'] : (function () {
    throw new RuntimeError('Variable "disabled" does not exist.', 43, $this->source);
})()), 'labelledBy' => Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [            // line 44
    (isset($context['labelledBy']) || array_key_exists('labelledBy', $context) ? $context['labelledBy'] : (function () {
        throw new RuntimeError('Variable "labelledBy" does not exist.', 44, $this->source);
    })()), (isset($context['hexLabelId']) || array_key_exists('hexLabelId', $context) ? $context['hexLabelId'] : (function () {
        throw new RuntimeError('Variable "hexLabelId" does not exist.', 44, $this->source);
    })())]), ' ')]], 35, $context, $this->getSourceContext());
            // line 45
            yield '
        </div>
    ';
            echo craft\helpers\Html::tag('div', ob_get_clean(),             // line 23
                (isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                    throw new RuntimeError('Variable "containerAttributes" does not exist.', 23, $this->source);
                })()));
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 22
        yield Twig\Extension\CoreExtension::spaceless($___internal_parse_0_);
        // line 50
        ob_start();
        // line 51
        yield "    new Craft.ColorInput('#";
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getFilter('namespaceInputId')->getCallable()((isset($context['containerId']) || array_key_exists('containerId', $context) ? $context['containerId'] : (function () {
            throw new RuntimeError('Variable "containerId" does not exist.', 51, $this->source);
        })())), 'html', null, true);
        yield "', {
        presets: ";
        // line 52
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((($context['presets']) ?? ([])));
        yield ',
    });
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        craft\helpers\Template::endProfile('template', '_includes/forms/color.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/color.twig';
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
        return [131 => 52,  126 => 51,  124 => 50,  122 => 22,  118 => 23,  114 => 45,  112 => 44,  111 => 43,  110 => 42,  109 => 39,  108 => 38,  107 => 37,  106 => 36,  105 => 35,  99 => 34,  95 => 32,  93 => 25,  90 => 30,  88 => 29,  86 => 27,  83 => 24,  80 => 23,  78 => 22,  75 => 21,  72 => 19,  70 => 18,  68 => 16,  67 => 14,  66 => 13,  63 => 12,  61 => 11,  59 => 10,  57 => 9,  55 => 8,  53 => 7,  51 => 6,  49 => 5,  47 => 4,  45 => 3,  43 => 1];
    }

    public function getSourceContext(): Source
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
    new Craft.ColorInput('#{{ containerId|namespaceInputId }}', {
        presets: {{ (presets ?? [])|json_encode|raw }},
    });
{% endjs -%}
", '_includes/forms/color.twig', '/tmp/packages/craft5/src/templates/_includes/forms/color.twig');
    }
}
