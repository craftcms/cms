<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__95c9e7ecbcd4f4af0a2548fa876d427d */
class __TwigTemplate_af0cfcd570a99b8ced43223ee93b9818 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__95c9e7ecbcd4f4af0a2548fa876d427d');
        // line 1
        echo base64_encode('foo');
        craft\helpers\Template::endProfile('template', '__string_template__95c9e7ecbcd4f4af0a2548fa876d427d');
    }

    public function getTemplateName()
    {
        return '__string_template__95c9e7ecbcd4f4af0a2548fa876d427d';
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
        return new Source('{{ "foo"|base64_encode }}', '__string_template__95c9e7ecbcd4f4af0a2548fa876d427d', '');
    }
}
