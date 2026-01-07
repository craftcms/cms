<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__44f5b0d0083c72c010c63803251c4235 */
class __TwigTemplate_b4a6ad144ae587040c78bb9229a20119 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__44f5b0d0083c72c010c63803251c4235');
        // line 1
        yield 'foo=';
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'foo', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'foo', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'foo', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'foo', [], 'any', false, false, false, 1));
        craft\helpers\Template::endProfile('template', '__string_template__44f5b0d0083c72c010c63803251c4235');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__44f5b0d0083c72c010c63803251c4235';
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
        return new Source('foo={{ (_variables.foo ?? object.foo)|raw }}', '__string_template__44f5b0d0083c72c010c63803251c4235', '');
    }
}
