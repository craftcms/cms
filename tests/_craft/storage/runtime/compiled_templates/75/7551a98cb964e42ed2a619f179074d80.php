<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__e9f0015ddbe07ecbe9e77f646195ea3e */
class __TwigTemplate_ac051ad7b682840c8e6a75c5c0c5c4da extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__e9f0015ddbe07ecbe9e77f646195ea3e');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->parseRefsFilter('{user:1:username}');
        craft\helpers\Template::endProfile('template', '__string_template__e9f0015ddbe07ecbe9e77f646195ea3e');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__e9f0015ddbe07ecbe9e77f646195ea3e';
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
        return new Source('{{ "{user:1:username}"|parseRefs }}', '__string_template__e9f0015ddbe07ecbe9e77f646195ea3e', '');
    }
}
