<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__5b344c7397f6ebeaaf184dc514d60b53 */
class __TwigTemplate_d96f26e5634af94c3111d17a62727f70 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__5b344c7397f6ebeaaf184dc514d60b53');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->replaceFilter('foo bar baz', ['/b(\\w+)/' => 'z$1', 'zaz' => 'zazzy']);
        craft\helpers\Template::endProfile('template', '__string_template__5b344c7397f6ebeaaf184dc514d60b53');
    }

    public function getTemplateName()
    {
        return '__string_template__5b344c7397f6ebeaaf184dc514d60b53';
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
        return new Source('{{ "foo bar baz"|replace({"/b(\\\\w+)/": "z$1", zaz: "zazzy"}) }}', '__string_template__5b344c7397f6ebeaaf184dc514d60b53', '');
    }
}
