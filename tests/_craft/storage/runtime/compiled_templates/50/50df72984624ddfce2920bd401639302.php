<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ba97295cf0f737617ba6648f66acfbfd */
class __TwigTemplate_65f948224b7a4599467907256addcbeb extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ba97295cf0f737617ba6648f66acfbfd');
        // line 1
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'exampleParam', [], 'any', false, false, false, 1));
        yield craft\helpers\Template::attribute($this->env, $this->source, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'object', [], 'any', false, false, false, 1))), 'exampleParam', [], 'any', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__ba97295cf0f737617ba6648f66acfbfd');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ba97295cf0f737617ba6648f66acfbfd';
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
        return new Source('{{ (_variables.exampleParam ?? object.exampleParam) |raw }}{{ (_variables.object ?? object.object).exampleParam |raw }}', '__string_template__ba97295cf0f737617ba6648f66acfbfd', '');
    }
}
