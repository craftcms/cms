<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__7762e12e03b67945dd7c71501ad9526a */
class __TwigTemplate_0b7a55738289e9aa170de78de05fc25b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__7762e12e03b67945dd7c71501ad9526a');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->indexOfFilter([2, 3, 4, 5], 3);
        craft\helpers\Template::endProfile('template', '__string_template__7762e12e03b67945dd7c71501ad9526a');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__7762e12e03b67945dd7c71501ad9526a';
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
        return new Source('{{ [2, 3, 4, 5]|indexOf(3) }}', '__string_template__7762e12e03b67945dd7c71501ad9526a', '');
    }
}
