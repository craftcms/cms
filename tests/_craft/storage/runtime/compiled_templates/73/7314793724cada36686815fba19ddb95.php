<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__1b6393b0240af0cc43453fe6f16ade37 */
class __TwigTemplate_a46c135025b3e1434d254229883502ce extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1b6393b0240af0cc43453fe6f16ade37');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->appendFilter('<p><span>bar</span></p>', '<span>foo</span>', 'replace');
        craft\helpers\Template::endProfile('template', '__string_template__1b6393b0240af0cc43453fe6f16ade37');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__1b6393b0240af0cc43453fe6f16ade37';
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
        return new Source('{{ "<p><span>bar</span></p>"|append("<span>foo</span>", "replace") }}', '__string_template__1b6393b0240af0cc43453fe6f16ade37', '');
    }
}
