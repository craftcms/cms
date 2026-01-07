<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _includes/forms/timeZone */
class __TwigTemplate_61b6f03f61e6b7863c55dea5d8269f1d extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/timeZone');
        // line 1
        $context['id'] ??= 'timezone'.Twig\Extension\CoreExtension::random($this->env->getCharset());
        // line 2
        yield '
';
        // line 3
        yield from $this->loadTemplate('_includes/forms/selectize', '_includes/forms/timeZone', 3)->unwrap()->yield(CoreExtension::merge($context, ['options' => craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 4
            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
            })()), 'cp', [], 'any', false, false, false, 4), 'getTimeZoneOptions', [(($context['offsetDate']) ?? (null))], 'method', false, false, false, 4), 'inputAttributes' => ['aria' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Time Zone', 'app')]]]));
        craft\helpers\Template::endProfile('template', '_includes/forms/timeZone');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/timeZone';
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
        return [49 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% set id = id ?? \"timezone#{random()}\" %}

{% include '_includes/forms/selectize' with {
    options: craft.cp.getTimeZoneOptions(offsetDate ?? null),
    inputAttributes: {
        aria: {
            label: 'Time Zone'|t('app'),
        }
    }
}%}
", '_includes/forms/timeZone', '/tmp/packages/craft5/src/templates/_includes/forms/timeZone.twig');
    }
}
