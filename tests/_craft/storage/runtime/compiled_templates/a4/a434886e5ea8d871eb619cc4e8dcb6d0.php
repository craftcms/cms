<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__bf30e993c8afaea6c3b955dd6251b7d8 */
class __TwigTemplate_25a6f384154c7e1671e5d8b7472828c8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__bf30e993c8afaea6c3b955dd6251b7d8');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->literalFilter('*foo*');
        craft\helpers\Template::endProfile('template', '__string_template__bf30e993c8afaea6c3b955dd6251b7d8');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__bf30e993c8afaea6c3b955dd6251b7d8';
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
        return new Source('{{ "*foo*"|literal }}', '__string_template__bf30e993c8afaea6c3b955dd6251b7d8', '');
    }
}
