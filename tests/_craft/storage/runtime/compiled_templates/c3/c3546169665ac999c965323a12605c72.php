<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__84c5e663d3f34e83ceb0f4e64e42020f */
class __TwigTemplate_082d1319af9f1c36238d21c71f83bbc2 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__84c5e663d3f34e83ceb0f4e64e42020f');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->replaceFilter('foo bar baz', '/f.*z/', 'qux');
        craft\helpers\Template::endProfile('template', '__string_template__84c5e663d3f34e83ceb0f4e64e42020f');
    }

    public function getTemplateName()
    {
        return '__string_template__84c5e663d3f34e83ceb0f4e64e42020f';
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
        return new Source('{{ "foo bar baz"|replace("/f.*z/", "qux") }}', '__string_template__84c5e663d3f34e83ceb0f4e64e42020f', '');
    }
}
