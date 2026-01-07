<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71 */
class __TwigTemplate_f201dc9a12a002c8577cf9852fef8ed0 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->markdownFilter('**Hello**');
        craft\helpers\Template::endProfile('template', '__string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71';
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
        return new Source('{{ "**Hello**"|md }}', '__string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71', '');
    }
}
