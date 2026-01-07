<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__4aaa475c995718b7eef34b3ae96aa7b7 */
class __TwigTemplate_72a96e2c0227e0808fb178d10af491fd extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4aaa475c995718b7eef34b3ae96aa7b7');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->widontFilter('foo bar baz');
        craft\helpers\Template::endProfile('template', '__string_template__4aaa475c995718b7eef34b3ae96aa7b7');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__4aaa475c995718b7eef34b3ae96aa7b7';
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
        return new Source('{{ "foo bar baz"|widont }}', '__string_template__4aaa475c995718b7eef34b3ae96aa7b7', '');
    }
}
