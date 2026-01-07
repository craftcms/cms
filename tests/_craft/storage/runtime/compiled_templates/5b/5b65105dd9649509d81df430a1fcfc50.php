<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__22bb608293867e3d9e829f559cbbf201 */
class __TwigTemplate_ec11d2dae0ce6d08600a190a857aff34 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__22bb608293867e3d9e829f559cbbf201');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->replaceFilter('foo bar baz', ['foo' => 'qux', 'bar' => 'quux', 'baz' => 'corge']);
        craft\helpers\Template::endProfile('template', '__string_template__22bb608293867e3d9e829f559cbbf201');
    }

    public function getTemplateName()
    {
        return '__string_template__22bb608293867e3d9e829f559cbbf201';
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
        return new Source('{{ "foo bar baz"|replace({foo: "qux", bar: "quux", baz: "corge"}) }}', '__string_template__22bb608293867e3d9e829f559cbbf201', '');
    }
}
