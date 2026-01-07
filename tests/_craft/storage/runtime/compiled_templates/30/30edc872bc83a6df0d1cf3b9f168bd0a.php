<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__a9d271a331c7c4c8bca3ebcba5488b11 */
class __TwigTemplate_508d586a11b92704e6ae674fcb7a525b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__a9d271a331c7c4c8bca3ebcba5488b11');
        // line 1
        yield craft\helpers\Html::redirectInput('A URL');
        craft\helpers\Template::endProfile('template', '__string_template__a9d271a331c7c4c8bca3ebcba5488b11');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__a9d271a331c7c4c8bca3ebcba5488b11';
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
        return new Source('{{ redirectInput("A URL") }}', '__string_template__a9d271a331c7c4c8bca3ebcba5488b11', '');
    }
}
