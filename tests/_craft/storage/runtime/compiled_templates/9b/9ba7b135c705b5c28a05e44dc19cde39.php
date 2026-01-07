<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/text */
class __TwigTemplate_093c9fd3892cb935f1a8f43b99a0d268 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/text');
        // line 1
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'app', []), 'deprecator', []), 'log', [0 => '_includes/forms/fld/text', 1 => 'is deprecated'], 'method'), 'html', null, true);
        // line 3
        $context['type'] ??= 'text';
        // line 4
        $context['autocomplete'] ??= false;
        // line 7
        $context['class'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'text', 1 => ((! ((        // line 9
            $context['size']) ?? (false))) ? ('fullwidth') : (null))]));
        // line 12
        $context['orientation'] ??= ((craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', [])) : (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 12, $this->source);
        })()), 'app', []), 'locale', []), 'getOrientation', [], 'method'));
        // line 14
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' =>         // line 15
(isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
    throw new RuntimeError('Variable "class" does not exist.', 15, $this->source);
})()), 'type' =>         // line 16
(isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
    throw new RuntimeError('Variable "type" does not exist.', 16, $this->source);
})()), 'id' => ((        // line 17
    $context['id']) ?? (false)), 'inputmode' => ((        // line 18
        $context['inputmode']) ?? (false)), 'size' => ((        // line 19
            $context['size']) ?? (false)), 'name' => ((        // line 20
                $context['name']) ?? (false)), 'value' => ((        // line 21
                    $context['value']) ?? (false)), 'maxlength' => ((        // line 22
                        $context['maxlength']) ?? (false)), 'autofocus' => ((((        // line 23
                            $context['autofocus']) ?? (false)) && craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                                throw new RuntimeError('Variable "currentUser" does not exist.', 23, $this->source);
                            })()), 'getAutofocusPreferred', [], 'method')) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                throw new RuntimeError('Variable "craft" does not exist.', 23, $this->source);
                            })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method')), 'autocomplete' => (($this->env->getTest('boolean')->getCallable()(        // line 24
                                (isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 24, $this->source);
                                })()))) ? ((((isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 24, $this->source);
                                })())) ? ('on') : ('off'))) : ((isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 24, $this->source);
                                })()))), 'autocorrect' => ((((        // line 25
                                    $context['autocorrect']) ?? (true))) ? (false) : ('off')), 'autocapitalize' => ((((        // line 26
                                        $context['autocapitalize']) ?? (true))) ? (false) : ('none')), 'disabled' => ((        // line 27
                                            $context['disabled']) ?? (false)), 'readonly' => ((        // line 28
                                                $context['readonly']) ?? (false)), 'title' => ((        // line 29
                                                    $context['title']) ?? (false)), 'placeholder' => ((        // line 30
                                                        $context['placeholder']) ?? (false)), 'step' => ((        // line 31
                                                            $context['step']) ?? (false)), 'min' => ((        // line 32
                                                                $context['min']) ?? (false)), 'max' => ((        // line 33
                                                                    $context['max']) ?? (false)), 'dir' =>         // line 34
(isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
    throw new RuntimeError('Variable "orientation" does not exist.', 34, $this->source);
})()), 'aria' => ['labelledby' => (((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 36
    ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', [])) : (false))) ? (false) : ((($context['labelledBy']) ?? (false)))), 'describedby' => ((        // line 37
        $context['describedBy']) ?? (false))], 'data-deprecated' => 'true', ], ((        // line 40
            $context['inputAttributes']) ?? ([])), true);
        // line 42
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 43
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 43, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 46
        if ((($context['showCharsLeft']) ?? (false))) {
            // line 47
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 47, $this->source);
            })()), ['data' => ['show-chars-left' =>             // line 49
(isset($context['showCharsLeft']) || array_key_exists('showCharsLeft', $context) ? $context['showCharsLeft'] : (function () {
    throw new RuntimeError('Variable "showCharsLeft" does not exist.', 49, $this->source);
})()), ], 'style' => [('padding-'.(((            // line 52
    (isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
        throw new RuntimeError('Variable "orientation" does not exist.', 52, $this->source);
    })()) == 'ltr')) ? ('right') : ('left'))) => (((($context['maxlength']) ?? (false))) ? ((((7.2 * $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['maxlength']) || array_key_exists('maxlength', $context) ? $context['maxlength'] : (function () {
        throw new RuntimeError('Variable "maxlength" does not exist.', 52, $this->source);
    })()))) + 14).'px')) : (''))]], true);
        }
        // line 57
        $context['input'] = $this->extensions['craft\web\twig\Extension']->tagFunction('input', (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
            throw new RuntimeError('Variable "inputAttributes" does not exist.', 57, $this->source);
        })()));
        // line 59
        if ((($context['unit']) ?? (false))) {
            // line 60
            echo '    <div class="flex">
        <div class="textwrapper">';
            // line 61
            echo isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
                throw new RuntimeError('Variable "input" does not exist.', 61, $this->source);
            })();
            echo '</div>
        <div class="label light">';
            // line 62
            echo twig_escape_filter($this->env, (isset($context['unit']) || array_key_exists('unit', $context) ? $context['unit'] : (function () {
                throw new RuntimeError('Variable "unit" does not exist.', 62, $this->source);
            })()), 'html', null, true);
            echo '</div>
    </div>';
        } else {
            // line 65
            echo isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
                throw new RuntimeError('Variable "input" does not exist.', 65, $this->source);
            })();
        }
        craft\helpers\Template::endProfile('template', '_includes/forms/text');
    }

    public function getTemplateName()
    {
        return '_includes/forms/text';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [102 => 65,  97 => 62,  93 => 61,  90 => 60,  88 => 59,  86 => 57,  83 => 52,  82 => 49,  81 => 47,  79 => 46,  76 => 43,  74 => 42,  72 => 40,  71 => 37,  70 => 36,  69 => 34,  68 => 33,  67 => 32,  66 => 31,  65 => 30,  64 => 29,  63 => 28,  62 => 27,  61 => 26,  60 => 25,  59 => 24,  58 => 23,  57 => 22,  56 => 21,  55 => 20,  54 => 19,  53 => 18,  52 => 17,  51 => 16,  50 => 15,  49 => 14,  47 => 12,  45 => 9,  44 => 7,  42 => 4,  40 => 3,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{{ craft.app.deprecator.log('_includes/forms/fld/text', 'is deprecated') }}

{%- set type = type ?? 'text' %}
{%- set autocomplete = autocomplete ?? false %}


{%- set class = (class ?? [])|explodeClass|merge([
    'text',
    not (size ?? false) ? 'fullwidth' : null,
]|filter) %}

{%- set orientation = orientation ?? inputAttributes.dir ?? craft.app.locale.getOrientation() %}

{%- set inputAttributes = {
    class: class,
    type: type,
    id: id ?? false,
    inputmode: inputmode ?? false,
    size: size ?? false,
    name: name ?? false,
    value: value ?? false,
    maxlength: maxlength ?? false,
    autofocus: (autofocus ?? false) and currentUser.getAutofocusPreferred() and not craft.app.request.isMobileBrowser(true),
    autocomplete: autocomplete is boolean ? (autocomplete ? 'on' : 'off') : autocomplete,
    autocorrect: (autocorrect ?? true) ? false : 'off',
    autocapitalize: (autocapitalize ?? true) ? false : 'none',
    disabled: disabled ?? false,
    readonly: readonly ?? false,
    title: title ?? false,
    placeholder: placeholder ?? false,
    step: step ?? false,
    min: min ?? false,
    max: max ?? false,
    dir: orientation,
    aria: {
        labelledby: (inputAttributes.aria.label ?? false) ? false : (labelledBy ?? false),
        describedby: describedBy ?? false,
    },
    'data-deprecated': 'true'
}|merge(inputAttributes ?? [], recursive=true) %}

{%- if block('attr') is defined %}
    {%- set inputAttributes = inputAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{%- endif %}

{%- if showCharsLeft ?? false %}
    {%- set inputAttributes = inputAttributes|merge({
        data: {
            'show-chars-left': showCharsLeft,
        },
        style: {
            (\"padding-#{orientation == 'ltr' ? 'right' : 'left'}\"): (maxlength ?? false) ? \"#{7.2*maxlength|length+14}px\",
        },
    }, recursive=true) %}
{%- endif %}

{%- set input = tag('input', inputAttributes) %}

{%- if unit ?? false %}
    <div class=\"flex\">
        <div class=\"textwrapper\">{{ input|raw }}</div>
        <div class=\"label light\">{{ unit }}</div>
    </div>
{%- else %}
    {{- input|raw }}
{%- endif %}
", '_includes/forms/text', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/text.twig');
    }
}
