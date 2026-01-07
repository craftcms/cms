<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/text */
class __TwigTemplate_8e5a676eff143b56027eb145cd7e2105 extends Template
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
        $context['type'] ??= 'text';
        // line 2
        $context['autocomplete'] ??= false;
        // line 4
        $context['class'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'text', 1 => ((! ((        // line 6
            $context['size']) ?? (false))) ? ('fullwidth') : (null))]));
        // line 9
        $context['orientation'] ??= ((craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', [])) : (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 9, $this->source);
        })()), 'app', []), 'locale', []), 'getOrientation', [], 'method'));
        // line 11
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' =>         // line 12
(isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
    throw new RuntimeError('Variable "class" does not exist.', 12, $this->source);
})()), 'type' =>         // line 13
(isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
    throw new RuntimeError('Variable "type" does not exist.', 13, $this->source);
})()), 'id' => ((        // line 14
    $context['id']) ?? (false)), 'inputmode' => ((        // line 15
        $context['inputmode']) ?? (false)), 'size' => ((        // line 16
            $context['size']) ?? (false)), 'name' => ((        // line 17
                $context['name']) ?? (false)), 'value' => ((        // line 18
                    $context['value']) ?? (false)), 'maxlength' => ((        // line 19
                        $context['maxlength']) ?? (false)), 'autofocus' => (((((        // line 20
                            $context['autofocus']) ?? (false)) && (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                                throw new RuntimeError('Variable "currentUser" does not exist.', 20, $this->source);
                            })())) && craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                                throw new RuntimeError('Variable "currentUser" does not exist.', 20, $this->source);
                            })()), 'getAutofocusPreferred', [], 'method')) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                throw new RuntimeError('Variable "craft" does not exist.', 20, $this->source);
                            })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method')), 'autocomplete' => (($this->env->getTest('boolean')->getCallable()(        // line 21
                                (isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 21, $this->source);
                                })()))) ? ((((isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 21, $this->source);
                                })())) ? ('on') : ('off'))) : ((isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 21, $this->source);
                                })()))), 'autocorrect' => ((((        // line 22
                                    $context['autocorrect']) ?? (true))) ? (false) : ('off')), 'autocapitalize' => ((((        // line 23
                                        $context['autocapitalize']) ?? (true))) ? (false) : ('none')), 'disabled' => ((        // line 24
                                            $context['disabled']) ?? (false)), 'readonly' => ((        // line 25
                                                $context['readonly']) ?? (false)), 'title' => ((        // line 26
                                                    $context['title']) ?? (false)), 'placeholder' => ((        // line 27
                                                        $context['placeholder']) ?? (false)), 'step' => ((        // line 28
                                                            $context['step']) ?? (false)), 'min' => ((        // line 29
                                                                $context['min']) ?? (false)), 'max' => ((        // line 30
                                                                    $context['max']) ?? (false)), 'dir' =>         // line 31
(isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
    throw new RuntimeError('Variable "orientation" does not exist.', 31, $this->source);
})()), 'aria' => ['labelledby' => (((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 33
    ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', [])) : (false))) ? (false) : ((($context['labelledBy']) ?? (false)))), 'describedby' => ((        // line 34
        $context['describedBy']) ?? (false))], ], ((        // line 36
            $context['inputAttributes']) ?? ([])), true);
        // line 38
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 39
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 39, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 42
        if ((($context['showCharsLeft']) ?? (false))) {
            // line 43
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 43, $this->source);
            })()), ['data' => ['show-chars-left' =>             // line 45
(isset($context['showCharsLeft']) || array_key_exists('showCharsLeft', $context) ? $context['showCharsLeft'] : (function () {
    throw new RuntimeError('Variable "showCharsLeft" does not exist.', 45, $this->source);
})()), ], 'style' => [('padding-'.(((            // line 48
    (isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
        throw new RuntimeError('Variable "orientation" does not exist.', 48, $this->source);
    })()) == 'ltr')) ? ('right') : ('left'))) => (((($context['maxlength']) ?? (false))) ? ((((7.2 * $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['maxlength']) || array_key_exists('maxlength', $context) ? $context['maxlength'] : (function () {
        throw new RuntimeError('Variable "maxlength" does not exist.', 48, $this->source);
    })()))) + 14).'px')) : (''))]], true);
        }
        // line 53
        $context['input'] = $this->extensions['craft\web\twig\Extension']->tagFunction('input', (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
            throw new RuntimeError('Variable "inputAttributes" does not exist.', 53, $this->source);
        })()));
        // line 55
        if ((($context['unit']) ?? (false))) {
            // line 56
            echo '    <div class="flex">
        <div class="textwrapper">';
            // line 57
            echo isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
                throw new RuntimeError('Variable "input" does not exist.', 57, $this->source);
            })();
            echo '</div>
        <div class="label light">';
            // line 58
            echo twig_escape_filter($this->env, (isset($context['unit']) || array_key_exists('unit', $context) ? $context['unit'] : (function () {
                throw new RuntimeError('Variable "unit" does not exist.', 58, $this->source);
            })()), 'html', null, true);
            echo '</div>
    </div>';
        } else {
            // line 61
            echo isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
                throw new RuntimeError('Variable "input" does not exist.', 61, $this->source);
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
        return [100 => 61,  95 => 58,  91 => 57,  88 => 56,  86 => 55,  84 => 53,  81 => 48,  80 => 45,  79 => 43,  77 => 42,  74 => 39,  72 => 38,  70 => 36,  69 => 34,  68 => 33,  67 => 31,  66 => 30,  65 => 29,  64 => 28,  63 => 27,  62 => 26,  61 => 25,  60 => 24,  59 => 23,  58 => 22,  57 => 21,  56 => 20,  55 => 19,  54 => 18,  53 => 17,  52 => 16,  51 => 15,  50 => 14,  49 => 13,  48 => 12,  47 => 11,  45 => 9,  43 => 6,  42 => 4,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{%- set type = type ?? 'text' %}
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
    autofocus: (autofocus ?? false) and currentUser and currentUser.getAutofocusPreferred() and not craft.app.request.isMobileBrowser(true),
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
