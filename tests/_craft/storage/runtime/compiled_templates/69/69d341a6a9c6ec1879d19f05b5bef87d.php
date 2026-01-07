<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/textarea */
class __TwigTemplate_bc4d500f9367b74a1bae0d449ce3e17d extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/textarea');
        // line 1
        $context['class'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['text', ((((        // line 3
            $context['disabled']) ?? (false))) ? ('disabled') : ('')), ((! ((        // line 4
                $context['cols']) ?? (false))) ? ('fullwidth') : (''))]));
        // line 7
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' =>         // line 8
(isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
    throw new RuntimeError('Variable "class" does not exist.', 8, $this->source);
})()), 'id' => ((        // line 9
    $context['id']) ?? (false)), 'inputmode' => ((        // line 10
        $context['inputmode']) ?? (false)), 'rows' => ((        // line 11
            $context['rows']) ?? (2)), 'cols' => ((        // line 12
                $context['cols']) ?? (50)), 'name' => ((        // line 13
                    $context['name']) ?? (false)), 'maxlength' => ((        // line 14
                        $context['maxlength']) ?? (false)), 'autofocus' => (((        // line 15
                            $context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
                            })()), 'app', [], 'any', false, false, false, 15), 'request', [], 'any', false, false, false, 15), 'isMobileBrowser', [true], 'method', false, false, false, 15)), 'disabled' => ((        // line 16
                                $context['disabled']) ?? (false)), 'readonly' => ((        // line 17
                                    $context['readonly']) ?? (false)), 'title' => ((        // line 18
                                        $context['title']) ?? (false)), 'placeholder' => ((        // line 19
                                            $context['placeholder']) ?? (false)), 'text' => ((        // line 20
                                                $context['value']) ?? ('')), 'aria' => ['describedby' => ((        // line 22
                                                    $context['describedBy']) ?? (false))], 'data' => ['show-chars-left' => ((        // line 25
                                                        $context['showCharsLeft']) ?? (false))]], ((        // line 27
                                                            $context['inputAttributes']) ?? ([])), true);
        // line 29
        if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
            // line 30
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 30, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 33
        yield $this->extensions['craft\web\twig\Extension']->tagFunction('textarea', (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
            throw new RuntimeError('Variable "inputAttributes" does not exist.', 33, $this->source);
        })()));
        yield '
';
        craft\helpers\Template::endProfile('template', '_includes/forms/textarea');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/textarea';
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
        return [70 => 33,  67 => 30,  65 => 29,  63 => 27,  62 => 25,  61 => 22,  60 => 20,  59 => 19,  58 => 18,  57 => 17,  56 => 16,  55 => 15,  54 => 14,  53 => 13,  52 => 12,  51 => 11,  50 => 10,  49 => 9,  48 => 8,  47 => 7,  45 => 4,  44 => 3,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{%- set class = (class ?? [])|explodeClass|merge([
    'text',
    (disabled ?? false) ? 'disabled',
    not (cols ?? false) ? 'fullwidth',
]|filter) %}

{%- set inputAttributes = {
    class: class,
    id: id ?? false,
    inputmode: inputmode ?? false,
    rows: rows ?? 2,
    cols: cols ?? 50,
    name: name ?? false,
    maxlength: maxlength ?? false,
    autofocus: (autofocus ?? false) and not craft.app.request.isMobileBrowser(true),
    disabled: disabled ?? false,
    readonly: readonly ?? false,
    title: title ?? false,
    placeholder: placeholder ?? false,
    text: value ?? '',
    aria: {
        describedby: describedBy ?? false,
    },
    data: {
        'show-chars-left': showCharsLeft ?? false,
    },
}|merge(inputAttributes ?? [], recursive=true) %}

{%- if block('attr') is defined %}
    {%- set inputAttributes = inputAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{%- endif %}

{{- tag('textarea', inputAttributes) }}
", '_includes/forms/textarea', '/tmp/packages/craft5/src/templates/_includes/forms/textarea.twig');
    }
}
