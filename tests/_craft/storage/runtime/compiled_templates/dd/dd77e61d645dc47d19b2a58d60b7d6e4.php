<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__95c9e7ecbcd4f4af0a2548fa876d427d */
class __TwigTemplate_3b32b3ffe7446b3975f09fc99ec79f8e extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__95c9e7ecbcd4f4af0a2548fa876d427d');
        // line 1
        yield base64_encode('foo');
        craft\helpers\Template::endProfile('template', '__string_template__95c9e7ecbcd4f4af0a2548fa876d427d');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__95c9e7ecbcd4f4af0a2548fa876d427d';
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
        return new Source('{{ "foo"|base64_encode }}', '__string_template__95c9e7ecbcd4f4af0a2548fa876d427d', '');
    }
}
