<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__7f8712fff11b58db42727f737c1c3c5a */
class __TwigTemplate_cce98889f96caaf6d845c35e6bb37c25 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__7f8712fff11b58db42727f737c1c3c5a');
        // line 1
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleArrayableParam', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleArrayableParam', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleArrayableParam', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'exampleArrayableParam', [], 'any', false, false, false, 1));
        yield craft\helpers\Template::attribute($this->env, $this->source, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'object', [], 'any', false, false, false, 1))), 'exampleArrayableParam', [], 'any', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__7f8712fff11b58db42727f737c1c3c5a');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__7f8712fff11b58db42727f737c1c3c5a';
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
        return [43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('{{ (_variables.exampleArrayableParam ?? object.exampleArrayableParam) |raw }}{{ (_variables.object ?? object.object).exampleArrayableParam |raw }}', '__string_template__7f8712fff11b58db42727f737c1c3c5a', '');
    }
}
