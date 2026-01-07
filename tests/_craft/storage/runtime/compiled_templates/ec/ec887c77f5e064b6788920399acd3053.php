<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ed39a993b3cf12df1bdffa52d1651a76 */
class __TwigTemplate_bb6a454dc91c26233aa5d821bd21acb9 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ed39a993b3cf12df1bdffa52d1651a76');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->mergeFilter(['f' => 'foo', 'b' => [0 => 'bar']], ['b' => [0 => 'baz']]));
        craft\helpers\Template::endProfile('template', '__string_template__ed39a993b3cf12df1bdffa52d1651a76');
    }

    public function getTemplateName()
    {
        return '__string_template__ed39a993b3cf12df1bdffa52d1651a76';
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
        return new Source('{{ {f: "foo", b: ["bar"]}|merge({b: ["baz"]})|json_encode }}', '__string_template__ed39a993b3cf12df1bdffa52d1651a76', '');
    }
}
