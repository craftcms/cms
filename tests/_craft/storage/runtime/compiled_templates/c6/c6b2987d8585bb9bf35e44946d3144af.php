<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__22bb608293867e3d9e829f559cbbf201 */
class __TwigTemplate_20c7aba2c1b5f2d6f243d94e9c3dd212 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__22bb608293867e3d9e829f559cbbf201');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->replaceFilter('foo bar baz', ['foo' => 'qux', 'bar' => 'quux', 'baz' => 'corge']);
        craft\helpers\Template::endProfile('template', '__string_template__22bb608293867e3d9e829f559cbbf201');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__22bb608293867e3d9e829f559cbbf201';
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
        return new Source('{{ "foo bar baz"|replace({foo: "qux", bar: "quux", baz: "corge"}) }}', '__string_template__22bb608293867e3d9e829f559cbbf201', '');
    }
}
