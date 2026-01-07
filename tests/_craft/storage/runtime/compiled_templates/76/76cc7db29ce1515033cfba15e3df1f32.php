<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/button */
class __TwigTemplate_5fce956618381cd4990ec93850a4f46d extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/button');
        // line 1
        $context['spinner'] ??= false;
        // line 2
        $context['busyMessage'] ??= false;
        // line 3
        $context['failureMessage'] ??= false;
        // line 4
        $context['retryMessage'] ??= false;
        // line 5
        $context['successMessage'] ??= false;
        // line 6
        $context['label'] ??= null;
        // line 7
        $context['labelHtml'] ??= null;
        // line 8
        $context['attributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['type' => ((        // line 9
            $context['type']) ?? ('button')), 'id' => ((        // line 10
                $context['id']) ?? (false)), 'class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass(((        // line 11
                    $context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'btn', 1 => 'btngroup-btn-first', 2 => ((! (        // line 14
                        (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                            throw new RuntimeError('Variable "label" does not exist.', 14, $this->source);
                        })()) || (isset($context['labelHtml']) || array_key_exists('labelHtml', $context) ? $context['labelHtml'] : (function () {
                            throw new RuntimeError('Variable "labelHtml" does not exist.', 14, $this->source);
                        })()))) ? ('btn-empty') : (null))])), 'data' => $this->extensions['craft\web\twig\Extension']->mergeFilter(['busy-message' =>         // line 17
                        (isset($context['busyMessage']) || array_key_exists('busyMessage', $context) ? $context['busyMessage'] : (function () {
                            throw new RuntimeError('Variable "busyMessage" does not exist.', 17, $this->source);
                        })()), 'failure-message' =>         // line 18
                        (isset($context['failureMessage']) || array_key_exists('failureMessage', $context) ? $context['failureMessage'] : (function () {
                            throw new RuntimeError('Variable "failureMessage" does not exist.', 18, $this->source);
                        })()), 'retry-message' =>         // line 19
                        (isset($context['retryMessage']) || array_key_exists('retryMessage', $context) ? $context['retryMessage'] : (function () {
                            throw new RuntimeError('Variable "retryMessage" does not exist.', 19, $this->source);
                        })()), 'success-message' =>         // line 20
                        (isset($context['successMessage']) || array_key_exists('successMessage', $context) ? $context['successMessage'] : (function () {
                            throw new RuntimeError('Variable "successMessage" does not exist.', 20, $this->source);
                        })()), ], (((craft\helpers\Template::attribute($this->env, $this->source,         // line 21
                            ($context['attributes'] ?? null), 'data', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['attributes'] ?? null), 'data', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['attributes'] ?? null), 'data', [])) : ([])))], ((        // line 22
                                $context['attributes']) ?? ([])));
        // line 24
        ob_start();
        // line 25
        echo '    ';
        if ((isset($context['spinner']) || array_key_exists('spinner', $context) ? $context['spinner'] : (function () {
            throw new RuntimeError('Variable "spinner" does not exist.', 25, $this->source);
        })())) {
            // line 26
            echo '        <div role="status" class="visually-hidden"></div>
    ';
        }
        // line 28
        echo '    ';
        ob_start();
        // line 29
        echo '        ';
        echo (((isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
            throw new RuntimeError('Variable "label" does not exist.', 29, $this->source);
        })()) || (isset($context['labelHtml']) || array_key_exists('labelHtml', $context) ? $context['labelHtml'] : (function () {
            throw new RuntimeError('Variable "labelHtml" does not exist.', 29, $this->source);
        })()))) ? ($this->extensions['craft\web\twig\Extension']->tagFunction('div', ['class' => [0 => 'label', 1 => 'inline-flex'], 'text' => ((        // line 31
            $context['label']) ?? (null)), 'html' => ((        // line 32
                $context['labelHtml']) ?? (null))])) : ('');
        // line 33
        echo '
        ';
        // line 34
        if ((isset($context['spinner']) || array_key_exists('spinner', $context) ? $context['spinner'] : (function () {
            throw new RuntimeError('Variable "spinner" does not exist.', 34, $this->source);
        })())) {
            // line 35
            echo '            <div class="spinner spinner-absolute">
                <span class="visually-hidden">';
            // line 36
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Loading', 'app'), 'html', null, true);
            echo '</span>
            </div>
        ';
        }
        // line 39
        echo '    ';
        echo craft\helpers\Html::tag('button', ob_get_clean(),         // line 28
            (isset($context['attributes']) || array_key_exists('attributes', $context) ? $context['attributes'] : (function () {
                throw new RuntimeError('Variable "attributes" does not exist.', 28, $this->source);
            })()));
        $___internal_parse_2_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 24
        echo twig_spaceless($___internal_parse_2_);
        craft\helpers\Template::endProfile('template', '_includes/forms/button');
    }

    public function getTemplateName()
    {
        return '_includes/forms/button';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [100 => 24,  97 => 28,  95 => 39,  89 => 36,  86 => 35,  84 => 34,  81 => 33,  79 => 32,  78 => 31,  76 => 29,  73 => 28,  69 => 26,  66 => 25,  64 => 24,  62 => 22,  61 => 21,  60 => 20,  59 => 19,  58 => 18,  57 => 17,  56 => 14,  55 => 11,  54 => 10,  53 => 9,  52 => 8,  50 => 7,  48 => 6,  46 => 5,  44 => 4,  42 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% set spinner = spinner ?? false -%}
{% set busyMessage = busyMessage ?? false %}
{% set failureMessage = failureMessage ?? false %}
{% set retryMessage = retryMessage ?? false %}
{% set successMessage = successMessage ?? false %}
{% set label = label ?? null %}
{% set labelHtml = labelHtml ?? null %}
{% set attributes = {
    type: type ?? 'button',
    id: id ?? false,
    class: (class ?? [])|explodeClass|merge([
        'btn',
        'btngroup-btn-first',
        not (label or labelHtml) ? 'btn-empty' : null,
    ]|filter),
    data: {
        'busy-message': busyMessage,
        'failure-message': failureMessage,
        'retry-message': retryMessage,
        'success-message': successMessage,
    }|merge(attributes.data ?? {}),
}|merge(attributes ?? {}) -%}

{% apply spaceless %}
    {% if spinner %}
        <div role=\"status\" class=\"visually-hidden\"></div>
    {% endif %}
    {% tag 'button' with attributes %}
        {{ (label or labelHtml) ? tag('div', {
            class: ['label', 'inline-flex'],
            text: label ?? null,
            html: labelHtml ?? null
        }) }}
        {% if spinner %}
            <div class=\"spinner spinner-absolute\">
                <span class=\"visually-hidden\">{{ 'Loading'|t('app') }}</span>
            </div>
        {% endif %}
    {% endtag %}
{% endapply -%}
", '_includes/forms/button', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/button.twig');
    }
}
