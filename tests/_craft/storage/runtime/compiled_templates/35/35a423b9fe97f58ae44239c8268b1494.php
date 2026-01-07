<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__c239d4ccaac382332d41e281f8cbcdf8 */
class __TwigTemplate_643849d8264570e820ae43a6a352066b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__c239d4ccaac382332d41e281f8cbcdf8');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->collectFunction((isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 1, $this->source);
        })()))::class;
        craft\helpers\Template::endProfile('template', '__string_template__c239d4ccaac382332d41e281f8cbcdf8');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__c239d4ccaac382332d41e281f8cbcdf8';
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
        return new Source('{{ className(collect(items)) }}', '__string_template__c239d4ccaac382332d41e281f8cbcdf8', '');
    }
}
