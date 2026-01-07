<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ced260b6069fc8d288ffda7b1ed7ef83 */
class __TwigTemplate_3dfc556dc0871d98052fa810dfe739f2 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ced260b6069fc8d288ffda7b1ed7ef83');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->tagFunction('p', ['text' => "<script>alert('Hello');</script>"]);
        craft\helpers\Template::endProfile('template', '__string_template__ced260b6069fc8d288ffda7b1ed7ef83');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ced260b6069fc8d288ffda7b1ed7ef83';
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
        return new Source("{{ tag(\"p\", {text: \"<script>alert('Hello');</script>\"}) }}", '__string_template__ced260b6069fc8d288ffda7b1ed7ef83', '');
    }
}
