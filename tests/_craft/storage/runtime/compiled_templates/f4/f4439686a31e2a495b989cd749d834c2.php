<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__f9815be4229bd644754d1612c5352cca */
class __TwigTemplate_8bef292da8c411d364a68877ad6ee99b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f9815be4229bd644754d1612c5352cca');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->currencyFilter(299, null, [], [], true);
        craft\helpers\Template::endProfile('template', '__string_template__f9815be4229bd644754d1612c5352cca');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__f9815be4229bd644754d1612c5352cca';
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
        return new Source('{{ 299|currency(stripZeros=true) }}', '__string_template__f9815be4229bd644754d1612c5352cca', '');
    }
}
