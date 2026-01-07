<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__dd78f12527644088c2a1a274e6fe24ab */
class __TwigTemplate_77ffb604c75f8d81d6c123e1193ef5c0 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__dd78f12527644088c2a1a274e6fe24ab');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->filesizeFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__dd78f12527644088c2a1a274e6fe24ab');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__dd78f12527644088c2a1a274e6fe24ab';
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
        return new Source('{{ "foo"|filesize }}', '__string_template__dd78f12527644088c2a1a274e6fe24ab', '');
    }
}
