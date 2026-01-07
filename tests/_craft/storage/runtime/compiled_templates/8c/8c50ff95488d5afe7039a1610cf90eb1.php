<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__7c6df4eef7054f8a49907d18ddd598f2 */
class __TwigTemplate_fe603f72dac96c59e41e402276df76f9 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__7c6df4eef7054f8a49907d18ddd598f2');
        // line 1
        echo twig_join_filter($this->extensions['craft\web\twig\Extension']->withoutKeyFilter(['a' => 'foo', 'b' => 'bar', 'c' => 'baz'], 'c'), ',');
        craft\helpers\Template::endProfile('template', '__string_template__7c6df4eef7054f8a49907d18ddd598f2');
    }

    public function getTemplateName()
    {
        return '__string_template__7c6df4eef7054f8a49907d18ddd598f2';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('{{ {a:"foo",b:"bar",c:"baz"}|withoutKey("c")|join(",") }}', '__string_template__7c6df4eef7054f8a49907d18ddd598f2', '');
    }
}
