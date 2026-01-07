<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6b46341588fb109523d6cef1bc0db880 */
class __TwigTemplate_7dfd928d16d712c0fcce543d9e818371 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6b46341588fb109523d6cef1bc0db880');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->tagFunction('p', ['text' => 'Hello', 'class' => 'foo']);
        craft\helpers\Template::endProfile('template', '__string_template__6b46341588fb109523d6cef1bc0db880');
    }

    public function getTemplateName()
    {
        return '__string_template__6b46341588fb109523d6cef1bc0db880';
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
        return new Source('{{ tag("p", {text: "Hello", class: "foo"}) }}', '__string_template__6b46341588fb109523d6cef1bc0db880', '');
    }
}
