<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__955216ba2eb382e000209944259eda8a */
class __TwigTemplate_2dbdd6b36564fe8286323c62316be4fd extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__955216ba2eb382e000209944259eda8a');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->kebabFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__955216ba2eb382e000209944259eda8a');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__955216ba2eb382e000209944259eda8a';
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
        return new Source('{{ "foo bar"|kebab }}', '__string_template__955216ba2eb382e000209944259eda8a', '');
    }
}
