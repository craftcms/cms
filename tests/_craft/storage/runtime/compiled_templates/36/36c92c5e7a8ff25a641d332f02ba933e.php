<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cf1e513d1e164d0e39beb54bfe00013c */
class __TwigTemplate_b104053fa25a31e03902173c3def1324 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cf1e513d1e164d0e39beb54bfe00013c');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->currencyFilter(null);
        craft\helpers\Template::endProfile('template', '__string_template__cf1e513d1e164d0e39beb54bfe00013c');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__cf1e513d1e164d0e39beb54bfe00013c';
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
        return new Source('{{ null|currency }}', '__string_template__cf1e513d1e164d0e39beb54bfe00013c', '');
    }
}
