<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__22ad0f5043cf61ff4d35c5cb4a134ae6 */
class __TwigTemplate_3809a25bd374c5f79d40748e2fd13f35 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__22ad0f5043cf61ff4d35c5cb4a134ae6');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->ucfirstFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__22ad0f5043cf61ff4d35c5cb4a134ae6');
    }

    public function getTemplateName()
    {
        return '__string_template__22ad0f5043cf61ff4d35c5cb4a134ae6';
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
        return new Source('{{ "foo bar"|ucfirst }}', '__string_template__22ad0f5043cf61ff4d35c5cb4a134ae6', '');
    }
}
