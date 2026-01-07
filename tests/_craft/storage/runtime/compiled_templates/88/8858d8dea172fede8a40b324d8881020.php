<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__11da3a29037169f5133c763bb2011c7b */
class __TwigTemplate_973db32411ff9c3361fef5bc22a68f65 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__11da3a29037169f5133c763bb2011c7b');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->unshiftFilter([0 => 'baz'], 'foo', 'bar'));
        craft\helpers\Template::endProfile('template', '__string_template__11da3a29037169f5133c763bb2011c7b');
    }

    public function getTemplateName()
    {
        return '__string_template__11da3a29037169f5133c763bb2011c7b';
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
        return new Source('{{ ["baz"]|unshift("foo", "bar")|json_encode }}', '__string_template__11da3a29037169f5133c763bb2011c7b', '');
    }
}
