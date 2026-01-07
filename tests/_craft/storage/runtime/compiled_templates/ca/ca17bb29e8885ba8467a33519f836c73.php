<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__989a01b74b4fea50142c9196528a9f97 */
class __TwigTemplate_8b4bca7d7edc641f955075a482d0be0e extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__989a01b74b4fea50142c9196528a9f97');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->camelFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__989a01b74b4fea50142c9196528a9f97');
    }

    public function getTemplateName()
    {
        return '__string_template__989a01b74b4fea50142c9196528a9f97';
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
        return new Source('{{ "foo bar"|camel }}', '__string_template__989a01b74b4fea50142c9196528a9f97', '');
    }
}
