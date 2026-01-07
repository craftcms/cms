<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__3a6699f57e72b7f59509bc38b8b25a7f */
class __TwigTemplate_f757044680f68dc128286c8332628dc8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__3a6699f57e72b7f59509bc38b8b25a7f');
        // line 1
        echo craft\helpers\Html::csrfInput();
        craft\helpers\Template::endProfile('template', '__string_template__3a6699f57e72b7f59509bc38b8b25a7f');
    }

    public function getTemplateName()
    {
        return '__string_template__3a6699f57e72b7f59509bc38b8b25a7f';
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
        return new Source('{{ csrfInput() }}', '__string_template__3a6699f57e72b7f59509bc38b8b25a7f', '');
    }
}
