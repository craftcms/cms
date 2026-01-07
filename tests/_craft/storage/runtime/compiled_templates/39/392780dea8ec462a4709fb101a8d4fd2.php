<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/field */
class __TwigTemplate_f7e4617304a9e5cede38c12da8d586ad extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'heading' => $this->block_heading(...),
            'input' => $this->block_input(...),
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_includes/forms/field');
        // line 1
        $context['id'] ??= 'field'.twig_random($this->env);
        // line 2
        $context['instructionsId'] ??= (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 2, $this->source);
        })()).'-instructions';
        // line 3
        $context['tipId'] ??= (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 3, $this->source);
        })()).'-tip';
        // line 4
        $context['warningId'] ??= (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 4, $this->source);
        })()).'-warning';
        // line 5
        $context['errorsId'] ??= (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 5, $this->source);
        })()).'-errors';
        // line 7
        $context['fieldLabel'] ??= ($context['label']) ?? ((($this->hasBlock('label', $context, $blocks)) ? ($this->renderBlock('label', $context, $blocks)) : (null)));
        // line 8
        $context['labelExtra'] ??= ($this->hasBlock('labelExtra', $context, $blocks)) ? ($this->renderBlock('labelExtra', $context, $blocks)) : (null);
        // line 9
        $context['instructions'] ??= ($this->hasBlock('instructions', $context, $blocks)) ? ($this->renderBlock('instructions', $context, $blocks)) : (null);
        // line 10
        $context['tip'] ??= ($this->hasBlock('tip', $context, $blocks)) ? ($this->renderBlock('tip', $context, $blocks)) : (null);
        // line 11
        $context['warning'] ??= ($this->hasBlock('warning', $context, $blocks)) ? ($this->renderBlock('warning', $context, $blocks)) : (null);
        // line 12
        $context['errors'] ??= null;
        // line 14
        $context['describedBy'] = (($context['describedBy']) ?? (twig_join_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => ((        // line 15
            (isset($context['errors']) || array_key_exists('errors', $context) ? $context['errors'] : (function () {
                throw new RuntimeError('Variable "errors" does not exist.', 15, $this->source);
            })())) ? ((isset($context['errorsId']) || array_key_exists('errorsId', $context) ? $context['errorsId'] : (function () {
                throw new RuntimeError('Variable "errorsId" does not exist.', 15, $this->source);
            })())) : (null)), 1 => ((        // line 16
                (isset($context['instructions']) || array_key_exists('instructions', $context) ? $context['instructions'] : (function () {
                    throw new RuntimeError('Variable "instructions" does not exist.', 16, $this->source);
                })())) ? ((isset($context['instructionsId']) || array_key_exists('instructionsId', $context) ? $context['instructionsId'] : (function () {
                    throw new RuntimeError('Variable "instructionsId" does not exist.', 16, $this->source);
                })())) : (null)), 2 => ((        // line 17
                    (isset($context['tip']) || array_key_exists('tip', $context) ? $context['tip'] : (function () {
                        throw new RuntimeError('Variable "tip" does not exist.', 17, $this->source);
                    })())) ? ((isset($context['tipId']) || array_key_exists('tipId', $context) ? $context['tipId'] : (function () {
                        throw new RuntimeError('Variable "tipId" does not exist.', 17, $this->source);
                    })())) : (null)), 3 => ((        // line 18
                        (isset($context['warning']) || array_key_exists('warning', $context) ? $context['warning'] : (function () {
                            throw new RuntimeError('Variable "warning" does not exist.', 18, $this->source);
                        })())) ? ((isset($context['warningId']) || array_key_exists('warningId', $context) ? $context['warningId'] : (function () {
                            throw new RuntimeError('Variable "warningId" does not exist.', 18, $this->source);
                        })())) : (null))]), ' ')) ?: null);
        // line 21
        if ($this->hasBlock('heading', $context, $blocks)) {
            // line 23
            ob_start();
            $this->displayBlock('heading', $context, $blocks);
            $context['heading'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 24
            $context['headingParts'] = twig_split_filter($this->env, (isset($context['heading']) || array_key_exists('heading', $context) ? $context['heading'] : (function () {
                throw new RuntimeError('Variable "heading" does not exist.', 24, $this->source);
            })()), '<!-- HEADING -->', 2);
            // line 25
            $context['headingPrefix'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 0, [], 'array', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 0, [], 'array') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 0, [], 'array')) : (null));
            // line 26
            $context['headingSuffix'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 1, [], 'array', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 1, [], 'array') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 1, [], 'array')) : (null));
        }
        // line 29
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 30
            $context['fieldAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((($context['fieldAttributes']) ?? ([])), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 33
        if ($this->hasBlock('input', $context, $blocks)) {
            // line 34
            ob_start();
            $this->displayBlock('input', $context, $blocks);
            $context['input'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        }
        // line 36
        echo '
';
        // line 37
        echo craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 37, $this->source);
        })()), 'cp', []), 'field', [0 => (isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
            throw new RuntimeError('Variable "input" does not exist.', 37, $this->source);
        })()), 1 => $context], 'method');
        echo '
';
        craft\helpers\Template::endProfile('template', '_includes/forms/field');
    }

    // line 23
    public function block_heading($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'heading');
        echo '<!-- HEADING -->';
        craft\helpers\Template::endProfile('block', 'heading');
    }

    // line 34
    public function block_input($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'input');
        echo isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
            throw new RuntimeError('Variable "input" does not exist.', 34, $this->source);
        })();
        craft\helpers\Template::endProfile('block', 'input');
    }

    public function getTemplateName()
    {
        return '_includes/forms/field';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [117 => 34,  108 => 23,  101 => 37,  98 => 36,  93 => 34,  91 => 33,  88 => 30,  86 => 29,  83 => 26,  81 => 25,  79 => 24,  75 => 23,  73 => 21,  71 => 18,  70 => 17,  69 => 16,  68 => 15,  67 => 14,  66 => 18,  65 => 17,  64 => 16,  63 => 15,  62 => 14,  60 => 12,  58 => 11,  56 => 10,  54 => 9,  52 => 8,  50 => 7,  48 => 5,  46 => 4,  44 => 3,  42 => 2,  40 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{%- set id = id ?? \"field#{random()}\" %}
{%- set instructionsId = instructionsId ?? \"#{id}-instructions\" %}
{%- set tipId = tipId ?? \"#{id}-tip\" %}
{%- set warningId = warningId ?? \"#{id}-warning\" %}
{%- set errorsId = errorsId ?? \"#{id}-errors\" %}

{%- set fieldLabel = fieldLabel ?? label ?? block('label') ?? null %}
{%- set labelExtra = labelExtra ?? block('labelExtra') ?? null %}
{%- set instructions = instructions ?? block('instructions') ?? null %}
{%- set tip = tip ?? block('tip') ?? null %}
{%- set warning = warning ?? block('warning') ?? null %}
{%- set errors = errors ?? null %}

{%- set describedBy = describedBy ?? [
    errors ? errorsId : null,
    instructions ? instructionsId : null,
    tip ? tipId : null,
    warning ? warningId : null,
]|filter|join(' ') ?: null %}

{%- if block('heading') is defined %}
    {#- Extract whatever HTML comes before and after parent() #}
    {%- set heading %}{% block heading %}<!-- HEADING -->{% endblock %}{% endset %}
    {%- set headingParts = heading|split('<!-- HEADING -->', 2) %}
    {%- set headingPrefix = headingParts[0] ?? null %}
    {%- set headingSuffix = headingParts[1] ?? null %}
{%- endif %}

{%- if block('attr') is defined %}
    {%- set fieldAttributes = (fieldAttributes ?? {})|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{%- endif %}

{%- if block('input') is defined %}
    {%- set input %}{% block input %}{{ input|raw }}{% endblock %}{% endset %}
{%- endif %}

{{ craft.cp.field(input, _context)|raw }}
", '_includes/forms/field', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/field.twig');
    }
}
