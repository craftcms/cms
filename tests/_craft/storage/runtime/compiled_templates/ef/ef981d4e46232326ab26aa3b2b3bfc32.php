<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2 */
class __TwigTemplate_50def75494405b25e54929326839ea52 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->pushFilter(['foo'], 'bar', 'baz'));
        craft\helpers\Template::endProfile('template', '__string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2';
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
        return new Source('{{ ["foo"]|push("bar", "baz")|json_encode }}', '__string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2', '');
    }
}
