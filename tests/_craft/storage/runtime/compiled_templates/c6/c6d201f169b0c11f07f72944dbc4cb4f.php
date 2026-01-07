<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__60caae63c30221334b0c9dcef73ec8af */
class __TwigTemplate_6c628e3e87217e2aabff9bc5c49c4655 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__60caae63c30221334b0c9dcef73ec8af');
        // line 1
        echo twig_join_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'foo', 1 => '', 2 => 'bar', 3 => '', 4 => 'baz']), ' ');
        craft\helpers\Template::endProfile('template', '__string_template__60caae63c30221334b0c9dcef73ec8af');
    }

    public function getTemplateName()
    {
        return '__string_template__60caae63c30221334b0c9dcef73ec8af';
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
        return new Source('{{ ["foo", "", "bar", "", "baz"]|filter|join(" ") }}', '__string_template__60caae63c30221334b0c9dcef73ec8af', '');
    }
}
