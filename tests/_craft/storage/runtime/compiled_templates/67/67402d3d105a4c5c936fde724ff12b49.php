<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__63836adee2c3bdbb414dea280bd717e5 */
class __TwigTemplate_fab6d1af97ea82fc93d6d74d6c5fc9cb extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__63836adee2c3bdbb414dea280bd717e5');
        // line 1
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'extraField', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'extraField', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'extraField', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'extraField', [], 'any', false, false, false, 1));
        yield craft\helpers\Template::attribute($this->env, $this->source, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'object', [], 'any', false, false, false, 1))), 'extraField', [], 'any', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__63836adee2c3bdbb414dea280bd717e5');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__63836adee2c3bdbb414dea280bd717e5';
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
        return new Source('{{ (_variables.extraField ?? object.extraField) |raw }}{{ (_variables.object ?? object.object).extraField |raw }}', '__string_template__63836adee2c3bdbb414dea280bd717e5', '');
    }
}
