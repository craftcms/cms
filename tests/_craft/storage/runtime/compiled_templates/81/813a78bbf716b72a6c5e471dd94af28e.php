<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__71dfbbee3d283407f7aa932b956ea0b8 */
class __TwigTemplate_5df343020081912e8f393e74c37cd037 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__71dfbbee3d283407f7aa932b956ea0b8');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->markdownFilter('**Hello**', null, true);
        craft\helpers\Template::endProfile('template', '__string_template__71dfbbee3d283407f7aa932b956ea0b8');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__71dfbbee3d283407f7aa932b956ea0b8';
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
        return new Source('{{ "**Hello**"|md(inlineOnly=true) }}', '__string_template__71dfbbee3d283407f7aa932b956ea0b8', '');
    }
}
