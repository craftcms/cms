<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__8df0773aaeb61faca0f78f89dd72b447 */
class __TwigTemplate_93c223431b34b841d15812bb8f686e83 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8df0773aaeb61faca0f78f89dd72b447');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->parseAttrFilter('foo'));
        craft\helpers\Template::endProfile('template', '__string_template__8df0773aaeb61faca0f78f89dd72b447');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__8df0773aaeb61faca0f78f89dd72b447';
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
        return new Source('{{ "foo"|parseAttr|json_encode }}', '__string_template__8df0773aaeb61faca0f78f89dd72b447', '');
    }
}
