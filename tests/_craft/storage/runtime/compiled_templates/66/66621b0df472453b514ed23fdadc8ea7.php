<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0a163c3b27a50144ff02ad6a054ef381 */
class __TwigTemplate_7a437ba77c3ffe0e69f0cbfcd85ebb67 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0a163c3b27a50144ff02ad6a054ef381');
        // line 1
        $this->extensions['craft\web\twig\Extension']->groupFilter('foo', 'bar');
        craft\helpers\Template::endProfile('template', '__string_template__0a163c3b27a50144ff02ad6a054ef381');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__0a163c3b27a50144ff02ad6a054ef381';
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
        return new Source('{% do "foo"|group("bar") %}', '__string_template__0a163c3b27a50144ff02ad6a054ef381', '');
    }
}
