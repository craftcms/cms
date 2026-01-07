<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2 */
class __TwigTemplate_cffe43d7c9670ff7baeb49059989c335 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->parseAttrFilter('<p id="foo" class="bar baz">Hello</p>'));
        craft\helpers\Template::endProfile('template', '__string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2';
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
        return new Source("{{ '<p id=\"foo\" class=\"bar baz\">Hello</p>'|parseAttr|json_encode }}", '__string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2', '');
    }
}
