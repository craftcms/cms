<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6c10341b34cac0d3b5a5e250a9759019 */
class __TwigTemplate_adf30cdd0c5eeb73af83a64cba5b5684 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6c10341b34cac0d3b5a5e250a9759019');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->markdownFilter('**Hello**');
        craft\helpers\Template::endProfile('template', '__string_template__6c10341b34cac0d3b5a5e250a9759019');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__6c10341b34cac0d3b5a5e250a9759019';
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
        return new Source('{{ "**Hello**"|markdown }}', '__string_template__6c10341b34cac0d3b5a5e250a9759019', '');
    }
}
