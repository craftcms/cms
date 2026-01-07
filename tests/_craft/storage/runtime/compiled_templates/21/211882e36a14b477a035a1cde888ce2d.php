<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _includes/forms/booleanMenu */
class __TwigTemplate_b88810f40dcdf870db769017c094d05a extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/booleanMenu');
        // line 1
        $context['id'] ??= 'booleanmenu'.Twig\Extension\CoreExtension::random($this->env->getCharset());
        // line 2
        if (((array_key_exists('value', $context) && Twig\Extension\CoreExtension::testEmpty((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
            throw new RuntimeError('Variable "value" does not exist.', 2, $this->source);
        })()))) && ! ((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
            throw new RuntimeError('Variable "value" does not exist.', 2, $this->source);
        })()) === null))) {
            // line 3
            yield '    ';
            $context['value'] = '0';
        }
        // line 5
        yield '
';
        // line 6
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['data' => ['boolean-menu' => true, 'target' => ((        // line 9
            $context['toggle']) ?? (false)), 'reverse-target' => ((        // line 10
                $context['reverseToggle']) ?? (false)), 'target-prefix' => false]], ((        // line 13
                    $context['inputAttributes']) ?? ([])), true);
        // line 14
        yield '
';
        // line 15
        $context['options'] = [['label' => ((        // line 17
            $context['yesLabel']) ?? ($this->extensions['craft\web\twig\Extension']->translateFilter('Yes', 'app'))), 'value' => '1', 'data' => ['status' => 'enabled']], ['label' => ((        // line 24
                $context['noLabel']) ?? ($this->extensions['craft\web\twig\Extension']->translateFilter('No', 'app'))), 'value' => '0', 'data' => ['status' => 'white']]];
        // line 31
        yield '
';
        // line 32
        if ((($context['includeEnvVars']) ?? (false))) {
            // line 33
            yield '    ';
            $context['options'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 33, $this->source);
            })()), craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 33, $this->source);
            })()), 'cp', [], 'any', false, false, false, 33), 'getBooleanEnvOptions', [], 'method', false, false, false, 33));
        }
        // line 35
        yield '
';
        // line 36
        yield from $this->loadTemplate('_includes/forms/selectize', '_includes/forms/booleanMenu', 36)->unwrap()->yield(CoreExtension::merge($context, ['includeEnvVars' => false, 'value' => ((        // line 38
            $context['value']) ?? ('0'))]));
        craft\helpers\Template::endProfile('template', '_includes/forms/booleanMenu');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/booleanMenu';
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
        return [79 => 38,  78 => 36,  75 => 35,  71 => 33,  69 => 32,  66 => 31,  64 => 24,  63 => 17,  62 => 15,  59 => 14,  57 => 13,  56 => 10,  55 => 9,  54 => 6,  51 => 5,  47 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% set id = id ?? \"booleanmenu#{random()}\" %}
{% if value is defined and value is empty and value is not same as(null) %}
    {% set value = '0' %}
{% endif %}

{% set inputAttributes = {
    data: {
        'boolean-menu': true,
        target: toggle ?? false,
        'reverse-target': reverseToggle ?? false,
        'target-prefix': false,
    },
}|merge(inputAttributes ?? [], recursive=true) %}

{% set options = [
    {
        label: yesLabel ?? 'Yes'|t('app'),
        value: '1',
        data: {
            status: 'enabled',
        },
    },
    {
        label: noLabel ?? 'No'|t('app'),
        value: '0',
        data: {
            status: 'white',
        },
    },
] %}

{% if includeEnvVars ?? false %}
    {% set options = options|merge(craft.cp.getBooleanEnvOptions()) %}
{% endif %}

{% include '_includes/forms/selectize' with {
    includeEnvVars: false,
    value: value ?? '0',
}%}
", '_includes/forms/booleanMenu', '/tmp/packages/craft5/src/templates/_includes/forms/booleanMenu.twig');
    }
}
