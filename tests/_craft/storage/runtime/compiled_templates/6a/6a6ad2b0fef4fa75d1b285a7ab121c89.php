<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__c6f1932fd35b1c006235e309f2d7aa69 */
class __TwigTemplate_a7256827a74ca9f128f309a0db0e4fff extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__c6f1932fd35b1c006235e309f2d7aa69');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->appendFilter('<p><span>foo</span></p>', '<span>bar</span>');
        craft\helpers\Template::endProfile('template', '__string_template__c6f1932fd35b1c006235e309f2d7aa69');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__c6f1932fd35b1c006235e309f2d7aa69';
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
        return new Source('{{ "<p><span>foo</span></p>"|append("<span>bar</span>") }}', '__string_template__c6f1932fd35b1c006235e309f2d7aa69', '');
    }
}
