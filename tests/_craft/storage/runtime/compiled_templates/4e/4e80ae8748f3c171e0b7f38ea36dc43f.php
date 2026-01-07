<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2 */
class __TwigTemplate_568274c1e83c032bb048685029a35158 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2');
        // line 1
        yield Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->mergeFilter(['foo'], ['bar', 'baz']), ' ');
        craft\helpers\Template::endProfile('template', '__string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2';
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
        return new Source('{{ ["foo"]|merge(["bar", "baz"])|join(" ") }}', '__string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2', '');
    }
}
