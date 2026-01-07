<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__b93f6074a306e1db3eb0582839a224e1 */
class __TwigTemplate_07cee5c19184da78932b9af3700dcda3 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b93f6074a306e1db3eb0582839a224e1');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->removeClassFilter('foo', 'foo');
        craft\helpers\Template::endProfile('template', '__string_template__b93f6074a306e1db3eb0582839a224e1');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b93f6074a306e1db3eb0582839a224e1';
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
        return new Source("{{ 'foo'|removeClass(\"foo\") }}", '__string_template__b93f6074a306e1db3eb0582839a224e1', '');
    }
}
