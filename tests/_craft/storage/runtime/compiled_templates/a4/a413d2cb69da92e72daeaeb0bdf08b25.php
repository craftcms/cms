<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__28911656061cf07ad2969da5f5aca6b2 */
class __TwigTemplate_136c249024ec07b71bd9e72419f42f98 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__28911656061cf07ad2969da5f5aca6b2');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->prependFilter('<p><span>bar</span></p>', '<span>foo</span>');
        craft\helpers\Template::endProfile('template', '__string_template__28911656061cf07ad2969da5f5aca6b2');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__28911656061cf07ad2969da5f5aca6b2';
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
        return new Source('{{ "<p><span>bar</span></p>"|prepend("<span>foo</span>") }}', '__string_template__28911656061cf07ad2969da5f5aca6b2', '');
    }
}
