<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__2cccfeaf03117b4173238e2657c83635 */
class __TwigTemplate_6b392ad9dc273113e9644c10f51a53a1 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2cccfeaf03117b4173238e2657c83635');
        // line 1
        yield craft\helpers\App::env('FROM_EMAIL_NAME');
        yield ' | ';
        yield craft\helpers\App::env('FROM_EMAIL_ADDRESS');
        craft\helpers\Template::endProfile('template', '__string_template__2cccfeaf03117b4173238e2657c83635');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__2cccfeaf03117b4173238e2657c83635';
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
        return new Source('{{ getenv("FROM_EMAIL_NAME") }} | {{ getenv("FROM_EMAIL_ADDRESS") }}', '__string_template__2cccfeaf03117b4173238e2657c83635', '');
    }
}
