<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__853924542caa1c6d05cc319ca013c2d3 */
class __TwigTemplate_4fab7b4b65a733bbffc2555bacdc04f6 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__853924542caa1c6d05cc319ca013c2d3');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->removeClassFilter('<div class="foo bar baz">', ['foo', 'bar']);
        craft\helpers\Template::endProfile('template', '__string_template__853924542caa1c6d05cc319ca013c2d3');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__853924542caa1c6d05cc319ca013c2d3';
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
        return new Source("{{ '<div class=\"foo bar baz\">'|removeClass([\"foo\", \"bar\"]) }}", '__string_template__853924542caa1c6d05cc319ca013c2d3', '');
    }
}
