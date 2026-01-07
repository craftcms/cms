<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__c8718136efe06d75ea00c7619cd842c2 */
class __TwigTemplate_7a5e4392e8570cbcd4c437a7e0757f64 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__c8718136efe06d75ea00c7619cd842c2');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['path']) || array_key_exists('path', $context) ? $context['path'] : (function () {
            throw new RuntimeError('Variable "path" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__c8718136efe06d75ea00c7619cd842c2');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__c8718136efe06d75ea00c7619cd842c2';
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
        return new Source('{{ svg(path) }}', '__string_template__c8718136efe06d75ea00c7619cd842c2', '');
    }
}
