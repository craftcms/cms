<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__78a1fc286ffb0bdec244a21000fc9d44 */
class __TwigTemplate_221b70753cfa558e37e9b642aed0b532 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__78a1fc286ffb0bdec244a21000fc9d44');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->currencyFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__78a1fc286ffb0bdec244a21000fc9d44');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__78a1fc286ffb0bdec244a21000fc9d44';
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
        return new Source('{{ "foo"|currency }}', '__string_template__78a1fc286ffb0bdec244a21000fc9d44', '');
    }
}
