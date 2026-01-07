<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ffcf0d17f5166ee0f0d4b92e35c588f4 */
class __TwigTemplate_b2cc4ec205ddf35b2b45662a32609ec7 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ffcf0d17f5166ee0f0d4b92e35c588f4');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->attrFilter('<p>Hey</p>', ['class' => 'foo']);
        craft\helpers\Template::endProfile('template', '__string_template__ffcf0d17f5166ee0f0d4b92e35c588f4');
    }

    public function getTemplateName()
    {
        return '__string_template__ffcf0d17f5166ee0f0d4b92e35c588f4';
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
        return new Source('{{ "<p>Hey</p>"|attr({class: "foo"}) }}', '__string_template__ffcf0d17f5166ee0f0d4b92e35c588f4', '');
    }
}
