<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd */
class __TwigTemplate_c9a981b625f991e81648089776569ce2 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->translateFilter('Source message with {var}', 'site', ['var' => (isset($context['myVar']) || array_key_exists('myVar', $context) ? $context['myVar'] : (function () {
            throw new RuntimeError('Variable "myVar" does not exist.', 1, $this->source);
        })())]);
        craft\helpers\Template::endProfile('template', '__string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd';
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
        return new Source('{{ "Source message with {var}"|t("site", {var: myVar}) }}', '__string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd', '');
    }
}
