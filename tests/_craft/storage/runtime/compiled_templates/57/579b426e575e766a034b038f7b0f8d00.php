<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__59d2c8b08ec6e7495726f67a831597d3 */
class __TwigTemplate_51a14240dfaa1ceb5de3874b1fe1dd73 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__59d2c8b08ec6e7495726f67a831597d3');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['myVar']) || array_key_exists('myVar', $context) ? $context['myVar'] : (function () {
            throw new RuntimeError('Variable "myVar" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__59d2c8b08ec6e7495726f67a831597d3');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__59d2c8b08ec6e7495726f67a831597d3';
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
        return new Source('{{ myVar|json_encode }}', '__string_template__59d2c8b08ec6e7495726f67a831597d3', '');
    }
}
