<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__1c712be337abe3a377c277caf86a0c42 */
class __TwigTemplate_57ce9c3acd45b0c1a4fe262d77767500 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1c712be337abe3a377c277caf86a0c42');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->tagFunction('p', ['html' => "<script>alert('Hello');</script>"]);
        craft\helpers\Template::endProfile('template', '__string_template__1c712be337abe3a377c277caf86a0c42');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__1c712be337abe3a377c277caf86a0c42';
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
        return new Source("{{ tag(\"p\", {html: \"<script>alert('Hello');</script>\"}) }}", '__string_template__1c712be337abe3a377c277caf86a0c42', '');
    }
}
