<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__f38551e939d3a5a942a8a873c0845c43 */
class __TwigTemplate_6f3a4e1352f86e26efaf0b230a1b5b69 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f38551e939d3a5a942a8a873c0845c43');
        // line 1
        yield ' ';
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'exampleParam', [], 'any', false, false, false, 1));
        craft\helpers\Template::endProfile('template', '__string_template__f38551e939d3a5a942a8a873c0845c43');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__f38551e939d3a5a942a8a873c0845c43';
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
        return new Source(' {{ (_variables.exampleParam ?? object.exampleParam)|raw }}', '__string_template__f38551e939d3a5a942a8a873c0845c43', '');
    }
}
