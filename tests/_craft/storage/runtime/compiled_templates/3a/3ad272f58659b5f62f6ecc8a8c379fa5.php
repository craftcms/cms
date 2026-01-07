<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__9fea54987b0ee6420c82a325bd9808f9 */
class __TwigTemplate_62bf3a7aa00d21c63f9c0b285d4e92c8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__9fea54987b0ee6420c82a325bd9808f9');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->numberFilter(1000, 2);
        craft\helpers\Template::endProfile('template', '__string_template__9fea54987b0ee6420c82a325bd9808f9');
    }

    public function getTemplateName()
    {
        return '__string_template__9fea54987b0ee6420c82a325bd9808f9';
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
        return new Source('{{ 1000|number(decimals=2) }}', '__string_template__9fea54987b0ee6420c82a325bd9808f9', '');
    }
}
