<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__19c9506db3b99a800d20277359994159 */
class __TwigTemplate_6011ba7083bbcd3a531b4fe8f47a8b57 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__19c9506db3b99a800d20277359994159');
        // line 1
        echo craft\helpers\App::parseEnv('$FROM_EMAIL_NAME');
        craft\helpers\Template::endProfile('template', '__string_template__19c9506db3b99a800d20277359994159');
    }

    public function getTemplateName()
    {
        return '__string_template__19c9506db3b99a800d20277359994159';
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
        return new Source('{{ parseEnv("$FROM_EMAIL_NAME") }}', '__string_template__19c9506db3b99a800d20277359994159', '');
    }
}
