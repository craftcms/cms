<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__8209d632d245e96352f91cf77d12420e */
class __TwigTemplate_9cce3ac553dc22a3a6d049ae8b78af36 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8209d632d245e96352f91cf77d12420e');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->replaceFilter('/foo/bar/', ['/foo/' => 'baz'], null, true);
        craft\helpers\Template::endProfile('template', '__string_template__8209d632d245e96352f91cf77d12420e');
    }

    public function getTemplateName()
    {
        return '__string_template__8209d632d245e96352f91cf77d12420e';
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
        return new Source('{{ "/foo/bar/"|replace({"/foo/": "baz"}, regex=true) }}', '__string_template__8209d632d245e96352f91cf77d12420e', '');
    }
}
