<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__2ec074eceab767462c72d66d27212907 */
class __TwigTemplate_f11bf390088b308762542121bb02ad06 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2ec074eceab767462c72d66d27212907');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->truncateFilter('Test foo bar', 8, '...');
        craft\helpers\Template::endProfile('template', '__string_template__2ec074eceab767462c72d66d27212907');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__2ec074eceab767462c72d66d27212907';
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
        return new Source('{{ "Test foo bar"|truncate(8, "...") }}', '__string_template__2ec074eceab767462c72d66d27212907', '');
    }
}
