<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/text */
class __TwigTemplate_a672ef77dc9b59dbe7343970e4604d26 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/text');
        // line 1
        $context['type'] ??= 'text';
        // line 2
        $context['autocomplete'] ??= false;
        // line 4
        $context['class'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['text', ((! ((        // line 6
            $context['size']) ?? (false))) ? ('fullwidth') : (null))]));
        // line 9
        $context['orientation'] ??= ((craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', [], 'any', true, true, false, 9) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', [], 'any', false, false, false, 9) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'dir', [], 'any', false, false, false, 9)) : (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 9, $this->source);
        })()), 'app', [], 'any', false, false, false, 9), 'locale', [], 'any', false, false, false, 9), 'getOrientation', [], 'method', false, false, false, 9));
        // line 10
        $context['expanded'] = ((((($context['expanded']) ?? ((($context['role']) ?? (false)))) == 'combobox')) ? ('false') : ((! (null === false) && false)));
        // line 12
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' =>         // line 13
(isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
    throw new RuntimeError('Variable "class" does not exist.', 13, $this->source);
})()), 'type' =>         // line 14
(isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
    throw new RuntimeError('Variable "type" does not exist.', 14, $this->source);
})()), 'id' => ((        // line 15
    $context['id']) ?? (false)), 'inputmode' => ((        // line 16
        $context['inputmode']) ?? (false)), 'size' => ((        // line 17
            $context['size']) ?? (false)), 'name' => ((        // line 18
                $context['name']) ?? (false)), 'value' => ((        // line 19
                    $context['value']) ?? (false)), 'maxlength' => ((        // line 20
                        $context['maxlength']) ?? (false)), 'autofocus' => (((((        // line 21
                            $context['autofocus']) ?? (false)) && (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                                throw new RuntimeError('Variable "currentUser" does not exist.', 21, $this->source);
                            })())) && craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                                throw new RuntimeError('Variable "currentUser" does not exist.', 21, $this->source);
                            })()), 'getAutofocusPreferred', [], 'method', false, false, false, 21)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                throw new RuntimeError('Variable "craft" does not exist.', 21, $this->source);
                            })()), 'app', [], 'any', false, false, false, 21), 'request', [], 'any', false, false, false, 21), 'isMobileBrowser', [true], 'method', false, false, false, 21)), 'autocomplete' => (($this->env->getTest('boolean')->getCallable()(        // line 22
                                (isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 22, $this->source);
                                })()))) ? ((((isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 22, $this->source);
                                })())) ? ('on') : ('off'))) : ((isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                                    throw new RuntimeError('Variable "autocomplete" does not exist.', 22, $this->source);
                                })()))), 'autocorrect' => ((((        // line 23
                                    $context['autocorrect']) ?? (true))) ? (false) : ('off')), 'autocapitalize' => ((((        // line 24
                                        $context['autocapitalize']) ?? (true))) ? (false) : ('none')), 'disabled' => ((        // line 25
                                            $context['disabled']) ?? (false)), 'readonly' => ((        // line 26
                                                $context['readonly']) ?? (false)), 'title' => ((        // line 27
                                                    $context['title']) ?? (false)), 'placeholder' => ((        // line 28
                                                        $context['placeholder']) ?? (false)), 'step' => ((        // line 29
                                                            $context['step']) ?? (false)), 'min' => ((        // line 30
                                                                $context['min']) ?? (false)), 'max' => ((        // line 31
                                                                    $context['max']) ?? (false)), 'dir' =>         // line 32
(isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
    throw new RuntimeError('Variable "orientation" does not exist.', 32, $this->source);
})()), 'role' => ((        // line 33
    $context['role']) ?? (false)), 'aria' => ['labelledby' => (((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 35
        ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true, false, 35), 'label', [], 'any', true, true, false, 35) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true, false, 35), 'label', [], 'any', false, false, false, 35) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true, false, 35), 'label', [], 'any', false, false, false, 35)) : (false))) ? (false) : ((($context['labelledBy']) ?? (false)))), 'describedby' => ((        // line 36
            $context['describedBy']) ?? (false)), 'expanded' =>         // line 37
    (isset($context['expanded']) || array_key_exists('expanded', $context) ? $context['expanded'] : (function () {
        throw new RuntimeError('Variable "expanded" does not exist.', 37, $this->source);
    })())]], ((        // line 39
        $context['inputAttributes']) ?? ([])), true);
        // line 41
        if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
            // line 42
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 42, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 45
        if ((($context['showCharsLeft']) ?? (false))) {
            // line 46
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 46, $this->source);
            })()), ['data' => ['show-chars-left' =>             // line 48
(isset($context['showCharsLeft']) || array_key_exists('showCharsLeft', $context) ? $context['showCharsLeft'] : (function () {
    throw new RuntimeError('Variable "showCharsLeft" does not exist.', 48, $this->source);
})())], 'style' => [('padding-'.(((            // line 51
    (isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
        throw new RuntimeError('Variable "orientation" does not exist.', 51, $this->source);
    })()) == 'ltr')) ? ('right') : ('left'))) => (((($context['maxlength']) ?? (false))) ? ((((7.2 * $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['maxlength']) || array_key_exists('maxlength', $context) ? $context['maxlength'] : (function () {
        throw new RuntimeError('Variable "maxlength" does not exist.', 51, $this->source);
    })()))) + 14).'px')) : (''))]], true);
        }
        // line 56
        $context['input'] = $this->extensions['craft\web\twig\Extension']->tagFunction('input', (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
            throw new RuntimeError('Variable "inputAttributes" does not exist.', 56, $this->source);
        })()));
        // line 58
        if ((($context['unit']) ?? (false))) {
            // line 59
            yield '    <div class="flex">
        <div class="textwrapper">';
            // line 60
            yield isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
                throw new RuntimeError('Variable "input" does not exist.', 60, $this->source);
            })();
            yield '</div>
        <div class="label light">';
            // line 61
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['unit']) || array_key_exists('unit', $context) ? $context['unit'] : (function () {
                throw new RuntimeError('Variable "unit" does not exist.', 61, $this->source);
            })()), 'html', null, true);
            yield '</div>
    </div>';
        } else {
            // line 64
            yield isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
                throw new RuntimeError('Variable "input" does not exist.', 64, $this->source);
            })();
        }
        craft\helpers\Template::endProfile('template', '_includes/forms/text');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/text';
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
        return [109 => 64,  104 => 61,  100 => 60,  97 => 59,  95 => 58,  93 => 56,  90 => 51,  89 => 48,  88 => 46,  86 => 45,  83 => 42,  81 => 41,  79 => 39,  78 => 37,  77 => 36,  76 => 35,  75 => 33,  74 => 32,  73 => 31,  72 => 30,  71 => 29,  70 => 28,  69 => 27,  68 => 26,  67 => 25,  66 => 24,  65 => 23,  64 => 22,  63 => 21,  62 => 20,  61 => 19,  60 => 18,  59 => 17,  58 => 16,  57 => 15,  56 => 14,  55 => 13,  54 => 12,  52 => 10,  50 => 9,  48 => 6,  47 => 4,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{%- set type = type ?? 'text' %}
{%- set autocomplete = autocomplete ?? false %}

{%- set class = (class ?? [])|explodeClass|merge([
    'text',
    not (size ?? false) ? 'fullwidth' : null,
]|filter) %}

{%- set orientation = orientation ?? inputAttributes.dir ?? craft.app.locale.getOrientation() %}
{%- set expanded = expanded ?? (role ?? false) == 'combobox' ? 'false' : false ?? false %}

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
    role: role ?? false,
    aria: {
        labelledby: (inputAttributes.aria.label ?? false) ? false : (labelledBy ?? false),
        describedby: describedBy ?? false,
        expanded: expanded,
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
", '_includes/forms/text', '/tmp/packages/craft5/src/templates/_includes/forms/text.twig');
    }
}
