<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__84c5e663d3f34e83ceb0f4e64e42020f */
class __TwigTemplate_1ee88a9dcb3172a6833bd00699896824 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__84c5e663d3f34e83ceb0f4e64e42020f');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->replaceFilter('foo bar baz', '/f.*z/', 'qux');
        craft\helpers\Template::endProfile('template', '__string_template__84c5e663d3f34e83ceb0f4e64e42020f');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__84c5e663d3f34e83ceb0f4e64e42020f';
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
        return new Source('{{ "foo bar baz"|replace("/f.*z/", "qux") }}', '__string_template__84c5e663d3f34e83ceb0f4e64e42020f', '');
    }
}
