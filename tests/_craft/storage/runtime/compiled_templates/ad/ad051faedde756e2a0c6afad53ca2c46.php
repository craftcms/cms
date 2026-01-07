<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__216fbd80d0f5e88d84fdd5f9230a2d1a */
class __TwigTemplate_98734d3ff141934d86d89a7384b715ec extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__216fbd80d0f5e88d84fdd5f9230a2d1a');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->lcfirstFilter('Foo Bar');
        craft\helpers\Template::endProfile('template', '__string_template__216fbd80d0f5e88d84fdd5f9230a2d1a');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__216fbd80d0f5e88d84fdd5f9230a2d1a';
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
        return new Source('{{ "Foo Bar"|lcfirst }}', '__string_template__216fbd80d0f5e88d84fdd5f9230a2d1a', '');
    }
}
