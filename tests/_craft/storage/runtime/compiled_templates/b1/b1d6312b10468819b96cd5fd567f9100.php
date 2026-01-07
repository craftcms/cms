<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b */
class __TwigTemplate_21a93451e5103a36977860d0bf0a9ce3 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b');
        // line 1
        echo craft\helpers\App::parseEnv('FROM_EMAIL_NAME');
        craft\helpers\Template::endProfile('template', '__string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b');
    }

    public function getTemplateName()
    {
        return '__string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b';
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
        return new Source('{{ parseEnv("FROM_EMAIL_NAME") }}', '__string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b', '');
    }
}
