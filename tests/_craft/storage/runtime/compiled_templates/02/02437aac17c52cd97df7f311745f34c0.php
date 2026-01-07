<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__7c6df4eef7054f8a49907d18ddd598f2 */
class __TwigTemplate_8b56f6b8bc8febc7b3581dacd9a73e18 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__7c6df4eef7054f8a49907d18ddd598f2');
        // line 1
        yield Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->withoutKeyFilter(['a' => 'foo', 'b' => 'bar', 'c' => 'baz'], 'c'), ',');
        craft\helpers\Template::endProfile('template', '__string_template__7c6df4eef7054f8a49907d18ddd598f2');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__7c6df4eef7054f8a49907d18ddd598f2';
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
        return new Source('{{ {a:"foo",b:"bar",c:"baz"}|withoutKey("c")|join(",") }}', '__string_template__7c6df4eef7054f8a49907d18ddd598f2', '');
    }
}
