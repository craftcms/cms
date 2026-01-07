<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ac7e8dd5fa954dc19f6040b3f19a1920 */
class __TwigTemplate_ba1c578093c60b7aaf6c32b74d3d3b18 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ac7e8dd5fa954dc19f6040b3f19a1920');
        // line 1
        yield Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->withoutFilter(['foo', 'bar', 'baz'], ['bar', 'baz']), ',');
        craft\helpers\Template::endProfile('template', '__string_template__ac7e8dd5fa954dc19f6040b3f19a1920');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ac7e8dd5fa954dc19f6040b3f19a1920';
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
        return new Source('{{ ["foo","bar","baz"]|without(["bar","baz"])|join(",") }}', '__string_template__ac7e8dd5fa954dc19f6040b3f19a1920', '');
    }
}
