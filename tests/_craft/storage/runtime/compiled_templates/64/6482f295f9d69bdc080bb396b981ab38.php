<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6d957a785af9f868297c40c48e40b3fb */
class __TwigTemplate_53eea514ffc7fb7f0ddc36a057597e7c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6d957a785af9f868297c40c48e40b3fb');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->snakeFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__6d957a785af9f868297c40c48e40b3fb');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__6d957a785af9f868297c40c48e40b3fb';
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
        return new Source('{{ "foo bar"|snake }}', '__string_template__6d957a785af9f868297c40c48e40b3fb', '');
    }
}
