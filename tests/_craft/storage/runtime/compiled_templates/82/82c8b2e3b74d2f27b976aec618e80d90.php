<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__93889955a11185d5f45536fb367753bb */
class __TwigTemplate_e7b5a8b308ddd37e64720f2cb5bf569b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__93889955a11185d5f45536fb367753bb');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->prependFilter('<p><span>bar</span></p>', '<span>foo</span>', 'replace');
        craft\helpers\Template::endProfile('template', '__string_template__93889955a11185d5f45536fb367753bb');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__93889955a11185d5f45536fb367753bb';
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
        return new Source('{{ "<p><span>bar</span></p>"|prepend("<span>foo</span>", "replace") }}', '__string_template__93889955a11185d5f45536fb367753bb', '');
    }
}
