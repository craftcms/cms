<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__989a01b74b4fea50142c9196528a9f97 */
class __TwigTemplate_9ef70a138523a9881703f6f145f8d21c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__989a01b74b4fea50142c9196528a9f97');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->camelFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__989a01b74b4fea50142c9196528a9f97');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__989a01b74b4fea50142c9196528a9f97';
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
        return new Source('{{ "foo bar"|camel }}', '__string_template__989a01b74b4fea50142c9196528a9f97', '');
    }
}
