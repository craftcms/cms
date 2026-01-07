<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__c0a4362d86319a3593ea6322a6f9a0c4 */
class __TwigTemplate_11736248877d3255e38d25d63c798ff1 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__c0a4362d86319a3593ea6322a6f9a0c4');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->gqlFunction('{ping}'));
        craft\helpers\Template::endProfile('template', '__string_template__c0a4362d86319a3593ea6322a6f9a0c4');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__c0a4362d86319a3593ea6322a6f9a0c4';
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
        return new Source('{{ gql("{ping}")|json_encode }}', '__string_template__c0a4362d86319a3593ea6322a6f9a0c4', '');
    }
}
