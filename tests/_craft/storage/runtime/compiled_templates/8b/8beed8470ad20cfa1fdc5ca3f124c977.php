<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__1c712be337abe3a377c277caf86a0c42 */
class __TwigTemplate_1f32f823577e03836a77d2b4694af834 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1c712be337abe3a377c277caf86a0c42');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->tagFunction('p', ['html' => "<script>alert('Hello');</script>"]);
        craft\helpers\Template::endProfile('template', '__string_template__1c712be337abe3a377c277caf86a0c42');
    }

    public function getTemplateName()
    {
        return '__string_template__1c712be337abe3a377c277caf86a0c42';
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
        return new Source("{{ tag(\"p\", {html: \"<script>alert('Hello');</script>\"}) }}", '__string_template__1c712be337abe3a377c277caf86a0c42', '');
    }
}
