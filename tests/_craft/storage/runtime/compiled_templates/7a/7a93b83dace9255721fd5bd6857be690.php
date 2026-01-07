<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ba911bff49bf8cd587015998e2d6577c */
class __TwigTemplate_8f0d38111e4b4dfcba23ede839d8ca2e extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ba911bff49bf8cd587015998e2d6577c');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->currencyFilter(299);
        craft\helpers\Template::endProfile('template', '__string_template__ba911bff49bf8cd587015998e2d6577c');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ba911bff49bf8cd587015998e2d6577c';
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
        return new Source('{{ 299|currency }}', '__string_template__ba911bff49bf8cd587015998e2d6577c', '');
    }
}
