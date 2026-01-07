<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__1c44e9b0bb8ae1567d41cf6591623186 */
class __TwigTemplate_d019700bf6277f6e37544d2fa96e7cb6 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1c44e9b0bb8ae1567d41cf6591623186');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->removeClassFilter('<div class="foo bar">', 'foo');
        craft\helpers\Template::endProfile('template', '__string_template__1c44e9b0bb8ae1567d41cf6591623186');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__1c44e9b0bb8ae1567d41cf6591623186';
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
        return new Source("{{ '<div class=\"foo bar\">'|removeClass(\"foo\") }}", '__string_template__1c44e9b0bb8ae1567d41cf6591623186', '');
    }
}
