<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__79d084889c1b890eaeccbd6d037b64eb */
class __TwigTemplate_3834a76e90ac3009272903f050d47b11 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__79d084889c1b890eaeccbd6d037b64eb');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->mergeFilter(['f' => 'foo', 'b' => [0 => 'bar']], ['b' => [0 => 'baz']], true));
        craft\helpers\Template::endProfile('template', '__string_template__79d084889c1b890eaeccbd6d037b64eb');
    }

    public function getTemplateName()
    {
        return '__string_template__79d084889c1b890eaeccbd6d037b64eb';
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
        return new Source('{{ {f: "foo", b: ["bar"]}|merge({b: ["baz"]}, recursive=true)|json_encode }}', '__string_template__79d084889c1b890eaeccbd6d037b64eb', '');
    }
}
