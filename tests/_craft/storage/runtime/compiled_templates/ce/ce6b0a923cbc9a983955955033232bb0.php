<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__33b56c77e04afa9204f960df374147ac */
class __TwigTemplate_82d000540368ff95e51b8d12ef8da9a2 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__33b56c77e04afa9204f960df374147ac');
        // line 1
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'slug', [], 'any', false, false, false, 1));
        craft\helpers\Template::endProfile('template', '__string_template__33b56c77e04afa9204f960df374147ac');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__33b56c77e04afa9204f960df374147ac';
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
        return new Source('{{ (_variables.slug ?? object.slug)|raw }}', '__string_template__33b56c77e04afa9204f960df374147ac', '');
    }
}
