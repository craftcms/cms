<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/button */
class __TwigTemplate_81cc6b1329f679cd511dd74cea48e7ac extends Template
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
        $context['enableLiveRegion'] = ((((isset($context['busyMessage']) || array_key_exists('busyMessage', $context) ? $context['busyMessage'] : (function () {
            throw new RuntimeError('Variable "busyMessage" does not exist.', 6, $this->source);
        })()) || (isset($context['failureMessage']) || array_key_exists('failureMessage', $context) ? $context['failureMessage'] : (function () {
            throw new RuntimeError('Variable "failureMessage" does not exist.', 6, $this->source);
        })())) || (isset($context['retryMessage']) || array_key_exists('retryMessage', $context) ? $context['retryMessage'] : (function () {
            throw new RuntimeError('Variable "retryMessage" does not exist.', 6, $this->source);
        })())) || (isset($context['successMessage']) || array_key_exists('successMessage', $context) ? $context['successMessage'] : (function () {
            throw new RuntimeError('Variable "successMessage" does not exist.', 6, $this->source);
        })()));
        // line 7
        $context['label'] ??= null;
        // line 8
        $context['labelHtml'] ??= null;
        // line 9
        $context['attributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['type' => ((        // line 10
            $context['type']) ?? ('button')), 'id' => ((        // line 11
                $context['id']) ?? (false)), 'class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass(((        // line 12
                    $context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['btn', 'btngroup-btn-first', ((! (        // line 15
                        (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                            throw new RuntimeError('Variable "label" does not exist.', 15, $this->source);
                        })()) || (isset($context['labelHtml']) || array_key_exists('labelHtml', $context) ? $context['labelHtml'] : (function () {
                            throw new RuntimeError('Variable "labelHtml" does not exist.', 15, $this->source);
                        })()))) ? ('btn-empty') : (null))])), 'data' => $this->extensions['craft\web\twig\Extension']->mergeFilter(['busy-message' =>         // line 18
                        (isset($context['busyMessage']) || array_key_exists('busyMessage', $context) ? $context['busyMessage'] : (function () {
                            throw new RuntimeError('Variable "busyMessage" does not exist.', 18, $this->source);
                        })()), 'failure-message' =>         // line 19
                        (isset($context['failureMessage']) || array_key_exists('failureMessage', $context) ? $context['failureMessage'] : (function () {
                            throw new RuntimeError('Variable "failureMessage" does not exist.', 19, $this->source);
                        })()), 'retry-message' =>         // line 20
                        (isset($context['retryMessage']) || array_key_exists('retryMessage', $context) ? $context['retryMessage'] : (function () {
                            throw new RuntimeError('Variable "retryMessage" does not exist.', 20, $this->source);
                        })()), 'success-message' =>         // line 21
                        (isset($context['successMessage']) || array_key_exists('successMessage', $context) ? $context['successMessage'] : (function () {
                            throw new RuntimeError('Variable "successMessage" does not exist.', 21, $this->source);
                        })())], (((craft\helpers\Template::attribute($this->env, $this->source,         // line 22
                            ($context['attributes'] ?? null), 'data', [], 'any', true, true, false, 22) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['attributes'] ?? null), 'data', [], 'any', false, false, false, 22) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['attributes'] ?? null), 'data', [], 'any', false, false, false, 22)) : ([])))], ((        // line 23
                                $context['attributes']) ?? ([])));
        // line 25
        $___internal_parse_2_ = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            // line 26
            yield '    ';
            if (((isset($context['spinner']) || array_key_exists('spinner', $context) ? $context['spinner'] : (function () {
                throw new RuntimeError('Variable "spinner" does not exist.', 26, $this->source);
            })()) && (isset($context['enableLiveRegion']) || array_key_exists('enableLiveRegion', $context) ? $context['enableLiveRegion'] : (function () {
                throw new RuntimeError('Variable "enableLiveRegion" does not exist.', 26, $this->source);
            })()))) {
                // line 27
                yield '        <div role="status" class="visually-hidden"></div>
    ';
            }
            // line 29
            yield '    ';
            ob_start();
            // line 30
            yield '        ';
            yield (((isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                throw new RuntimeError('Variable "label" does not exist.', 30, $this->source);
            })()) || (isset($context['labelHtml']) || array_key_exists('labelHtml', $context) ? $context['labelHtml'] : (function () {
                throw new RuntimeError('Variable "labelHtml" does not exist.', 30, $this->source);
            })()))) ? ($this->extensions['craft\web\twig\Extension']->tagFunction('div', ['class' => ['label', 'inline-flex'], 'text' => ((            // line 32
                $context['label']) ?? (null)), 'html' => ((            // line 33
                    $context['labelHtml']) ?? (null))])) : ('');
            // line 34
            yield '
        ';
            // line 35
            if ((isset($context['spinner']) || array_key_exists('spinner', $context) ? $context['spinner'] : (function () {
                throw new RuntimeError('Variable "spinner" does not exist.', 35, $this->source);
            })())) {
                // line 36
                yield '            <div class="spinner spinner-absolute">
                <span class="visually-hidden">';
                // line 37
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Loading', 'app'), 'html', null, true);
                yield '</span>
            </div>
        ';
            }
            // line 40
            yield '    ';
            echo craft\helpers\Html::tag('button', ob_get_clean(),             // line 29
                (isset($context['attributes']) || array_key_exists('attributes', $context) ? $context['attributes'] : (function () {
                    throw new RuntimeError('Variable "attributes" does not exist.', 29, $this->source);
                })()));
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 25
        yield Twig\Extension\CoreExtension::spaceless($___internal_parse_2_);
        craft\helpers\Template::endProfile('template', '_includes/forms/button');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/button';
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
        return [108 => 25,  104 => 29,  102 => 40,  96 => 37,  93 => 36,  91 => 35,  88 => 34,  86 => 33,  85 => 32,  83 => 30,  80 => 29,  76 => 27,  73 => 26,  71 => 25,  69 => 23,  68 => 22,  67 => 21,  66 => 20,  65 => 19,  64 => 18,  63 => 15,  62 => 12,  61 => 11,  60 => 10,  59 => 9,  57 => 8,  55 => 7,  53 => 6,  51 => 5,  49 => 4,  47 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% set spinner = spinner ?? false -%}
{% set busyMessage = busyMessage ?? false %}
{% set failureMessage = failureMessage ?? false %}
{% set retryMessage = retryMessage ?? false %}
{% set successMessage = successMessage ?? false %}
{% set enableLiveRegion = busyMessage or failureMessage or retryMessage or successMessage %}
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
    {% if spinner and enableLiveRegion %}
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
", '_includes/forms/button', '/tmp/packages/craft5/src/templates/_includes/forms/button.twig');
    }
}
