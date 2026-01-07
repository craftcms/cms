<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__74f311564a44ef94c09b7d683fe15a0e */
class __TwigTemplate_24ef83174e671999259ed6cfdc8c55e0 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__74f311564a44ef94c09b7d683fe15a0e');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->attrFilter('Hey', ['class' => 'foo']);
        craft\helpers\Template::endProfile('template', '__string_template__74f311564a44ef94c09b7d683fe15a0e');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__74f311564a44ef94c09b7d683fe15a0e';
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
        return new Source('{{ "Hey"|attr({class: "foo"}) }}', '__string_template__74f311564a44ef94c09b7d683fe15a0e', '');
    }
}
