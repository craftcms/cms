<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ddc287aeeddb1e35d30840319cc6faa3 */
class __TwigTemplate_31e45f3a464e42a06ad88bb6a4c35028 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ddc287aeeddb1e35d30840319cc6faa3');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->ucwordsFilter($this->env, 'foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__ddc287aeeddb1e35d30840319cc6faa3');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ddc287aeeddb1e35d30840319cc6faa3';
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
        return new Source('{{ "foo bar"|ucwords }}', '__string_template__ddc287aeeddb1e35d30840319cc6faa3', '');
    }
}
