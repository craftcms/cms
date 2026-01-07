<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/field */
class __TwigTemplate_416beb21b9f67b83b242f97dd046f108 extends Template
{
    private readonly Source $source;

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

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_includes/forms/field');
        // line 1
        $context['id'] ??= 'field'.Twig\Extension\CoreExtension::random($this->env->getCharset());
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
        $context['fieldLabel'] ??= ($context['label']) ?? ((($this->unwrap()->hasBlock('label', $context, $blocks)) ? ($this->unwrap()->renderBlock('label', $context, $blocks)) : (null)));
        // line 8
        $context['labelExtra'] ??= ($this->unwrap()->hasBlock('labelExtra', $context, $blocks)) ? ($this->unwrap()->renderBlock('labelExtra', $context, $blocks)) : (null);
        // line 9
        $context['instructions'] ??= ($this->unwrap()->hasBlock('instructions', $context, $blocks)) ? ($this->unwrap()->renderBlock('instructions', $context, $blocks)) : (null);
        // line 10
        $context['tip'] ??= ($this->unwrap()->hasBlock('tip', $context, $blocks)) ? ($this->unwrap()->renderBlock('tip', $context, $blocks)) : (null);
        // line 11
        $context['warning'] ??= ($this->unwrap()->hasBlock('warning', $context, $blocks)) ? ($this->unwrap()->renderBlock('warning', $context, $blocks)) : (null);
        // line 12
        $context['errors'] ??= null;
        // line 14
        $context['describedBy'] = (((($context['describedBy']) ?? (Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [((        // line 15
            (isset($context['errors']) || array_key_exists('errors', $context) ? $context['errors'] : (function () {
                throw new RuntimeError('Variable "errors" does not exist.', 15, $this->source);
            })())) ? ((isset($context['errorsId']) || array_key_exists('errorsId', $context) ? $context['errorsId'] : (function () {
                throw new RuntimeError('Variable "errorsId" does not exist.', 15, $this->source);
            })())) : (null)), ((        // line 16
                (isset($context['instructions']) || array_key_exists('instructions', $context) ? $context['instructions'] : (function () {
                    throw new RuntimeError('Variable "instructions" does not exist.', 16, $this->source);
                })())) ? ((isset($context['instructionsId']) || array_key_exists('instructionsId', $context) ? $context['instructionsId'] : (function () {
                    throw new RuntimeError('Variable "instructionsId" does not exist.', 16, $this->source);
                })())) : (null)), ((        // line 17
                    (isset($context['tip']) || array_key_exists('tip', $context) ? $context['tip'] : (function () {
                        throw new RuntimeError('Variable "tip" does not exist.', 17, $this->source);
                    })())) ? ((isset($context['tipId']) || array_key_exists('tipId', $context) ? $context['tipId'] : (function () {
                        throw new RuntimeError('Variable "tipId" does not exist.', 17, $this->source);
                    })())) : (null)), ((        // line 18
                        (isset($context['warning']) || array_key_exists('warning', $context) ? $context['warning'] : (function () {
                            throw new RuntimeError('Variable "warning" does not exist.', 18, $this->source);
                        })())) ? ((isset($context['warningId']) || array_key_exists('warningId', $context) ? $context['warningId'] : (function () {
                            throw new RuntimeError('Variable "warningId" does not exist.', 18, $this->source);
                        })())) : (null))]), ' ')))) ?: (null));
        // line 21
        if ($this->unwrap()->hasBlock('heading', $context, $blocks)) {
            // line 23
            $context['heading'] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $blocks) {
                yield from $this->unwrap()->yieldBlock('heading', $context, $blocks);
                yield from [];
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 24
            $context['headingParts'] = Twig\Extension\CoreExtension::split($this->env->getCharset(), (isset($context['heading']) || array_key_exists('heading', $context) ? $context['heading'] : (function () {
                throw new RuntimeError('Variable "heading" does not exist.', 24, $this->source);
            })()), '<!-- HEADING -->', 2);
            // line 25
            $context['headingPrefix'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 0, [], 'array', true, true, false, 25) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 0, [], 'array', false, false, false, 25) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 0, [], 'array', false, false, false, 25)) : (null));
            // line 26
            $context['headingSuffix'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 1, [], 'array', true, true, false, 26) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 1, [], 'array', false, false, false, 26) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['headingParts'] ?? null), 1, [], 'array', false, false, false, 26)) : (null));
        }
        // line 29
        if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
            // line 30
            $context['fieldAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((($context['fieldAttributes']) ?? ([])), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 33
        if ($this->unwrap()->hasBlock('input', $context, $blocks)) {
            // line 34
            $context['input'] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $blocks) {
                yield from $this->unwrap()->yieldBlock('input', $context, $blocks);
                yield from [];
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
        }
        // line 36
        yield '
';
        // line 37
        yield craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 37, $this->source);
        })()), 'cp', [], 'any', false, false, false, 37), 'field', [(isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
            throw new RuntimeError('Variable "input" does not exist.', 37, $this->source);
        })()), $context], 'method', false, false, false, 37);
        yield '
';
        craft\helpers\Template::endProfile('template', '_includes/forms/field');
        yield from [];
    }

    // line 23
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_heading(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'heading');
        yield '<!-- HEADING -->';
        craft\helpers\Template::endProfile('block', 'heading');
        yield from [];
    }

    // line 34
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_input(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'input');
        yield isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
            throw new RuntimeError('Variable "input" does not exist.', 34, $this->source);
        })();
        craft\helpers\Template::endProfile('block', 'input');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/field';
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
        return [124 => 34,  111 => 23,  103 => 37,  100 => 36,  94 => 34,  92 => 33,  89 => 30,  87 => 29,  84 => 26,  82 => 25,  80 => 24,  75 => 23,  73 => 21,  71 => 18,  70 => 17,  69 => 16,  68 => 15,  67 => 14,  65 => 12,  63 => 11,  61 => 10,  59 => 9,  57 => 8,  55 => 7,  53 => 5,  51 => 4,  49 => 3,  47 => 2,  45 => 1];
    }

    public function getSourceContext(): Source
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
", '_includes/forms/field', '/tmp/packages/craft5/src/templates/_includes/forms/field.twig');
    }
}
