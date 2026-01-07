<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ad7517b1647c8ac809588f218b3317f5 */
class __TwigTemplate_57879458f5400ec014b7e8b72ad8bb67 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ad7517b1647c8ac809588f218b3317f5');
        // line 1
        echo twig_join_filter($this->extensions['craft\web\twig\Extension']->withoutKeyFilter(['a' => 'foo', 'b' => 'bar', 'c' => 'baz'], [0 => 'b', 1 => 'c']), ',');
        craft\helpers\Template::endProfile('template', '__string_template__ad7517b1647c8ac809588f218b3317f5');
    }

    public function getTemplateName()
    {
        return '__string_template__ad7517b1647c8ac809588f218b3317f5';
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
        return new Source('{{ {a:"foo",b:"bar",c:"baz"}|withoutKey(["b","c"])|join(",") }}', '__string_template__ad7517b1647c8ac809588f218b3317f5', '');
    }
}
