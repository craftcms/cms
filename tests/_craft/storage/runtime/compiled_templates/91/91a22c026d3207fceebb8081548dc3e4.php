<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__3b96a59f4dc2581a2dd2f5bc93e777f8 */
class __TwigTemplate_2a084cb8da26520ea011140b4f4674ea extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__3b96a59f4dc2581a2dd2f5bc93e777f8');
        // line 1
        echo base64_decode('Zm9v');
        craft\helpers\Template::endProfile('template', '__string_template__3b96a59f4dc2581a2dd2f5bc93e777f8');
    }

    public function getTemplateName()
    {
        return '__string_template__3b96a59f4dc2581a2dd2f5bc93e777f8';
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
        return new Source("{{ 'Zm9v'|base64_decode }}", '__string_template__3b96a59f4dc2581a2dd2f5bc93e777f8', '');
    }
}
