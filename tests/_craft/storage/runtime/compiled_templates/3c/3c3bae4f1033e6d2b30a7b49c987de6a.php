<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cf7d176858c7d56f36824eb9a2508c45 */
class __TwigTemplate_d931c21ce5ba18cf3d67cf8635fdbf9b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cf7d176858c7d56f36824eb9a2508c45');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->removeClassFilter('<div class="foo">', 'foo');
        craft\helpers\Template::endProfile('template', '__string_template__cf7d176858c7d56f36824eb9a2508c45');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__cf7d176858c7d56f36824eb9a2508c45';
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
        return new Source("{{ '<div class=\"foo\">'|removeClass(\"foo\") }}", '__string_template__cf7d176858c7d56f36824eb9a2508c45', '');
    }
}
