<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__91cbba98dcb8ab083a15ef0bb53d5fbe */
class __TwigTemplate_8730f927e3a278a60a82c3050752c0ba extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__91cbba98dcb8ab083a15ef0bb53d5fbe');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->indexOfFilter('Im a string', 'a');
        craft\helpers\Template::endProfile('template', '__string_template__91cbba98dcb8ab083a15ef0bb53d5fbe');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__91cbba98dcb8ab083a15ef0bb53d5fbe';
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
        return new Source('{{ "Im a string"|indexOf("a") }}', '__string_template__91cbba98dcb8ab083a15ef0bb53d5fbe', '');
    }
}
