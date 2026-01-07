<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0a199a41e5573950c20940247f3d137a */
class __TwigTemplate_731279c576ac0d54cd87b28f572ba48e extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0a199a41e5573950c20940247f3d137a');
        // line 1
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'id', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'id', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'id', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'id', [], 'any', false, false, false, 1));
        craft\helpers\Template::endProfile('template', '__string_template__0a199a41e5573950c20940247f3d137a');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__0a199a41e5573950c20940247f3d137a';
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
        return new Source('{{ (_variables.id ?? object.id)|raw }}', '__string_template__0a199a41e5573950c20940247f3d137a', '');
    }
}
