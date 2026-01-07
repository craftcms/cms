<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__2252c80dd66c19e78c44e4b072cb7aa4 */
class __TwigTemplate_20cbabca3bc8c8b12b1907a05b2a62bc extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2252c80dd66c19e78c44e4b072cb7aa4');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->translateFilter('Source message', 'invalidCategory');
        craft\helpers\Template::endProfile('template', '__string_template__2252c80dd66c19e78c44e4b072cb7aa4');
    }

    public function getTemplateName()
    {
        return '__string_template__2252c80dd66c19e78c44e4b072cb7aa4';
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
        return new Source('{{ "Source message"|t("invalidCategory") }}', '__string_template__2252c80dd66c19e78c44e4b072cb7aa4', '');
    }
}
