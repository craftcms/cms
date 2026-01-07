<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cb3b92850811006e7714eebc3e0f9957 */
class __TwigTemplate_eda472cc10f4da26736207cacec16f2c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cb3b92850811006e7714eebc3e0f9957');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->pascalFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__cb3b92850811006e7714eebc3e0f9957');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__cb3b92850811006e7714eebc3e0f9957';
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
        return new Source('{{ "foo bar"|pascal }}', '__string_template__cb3b92850811006e7714eebc3e0f9957', '');
    }
}
