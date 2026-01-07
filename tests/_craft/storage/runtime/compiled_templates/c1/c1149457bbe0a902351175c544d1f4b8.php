<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__4e83fcc8047a877726edf650070523fd */
class __TwigTemplate_9e2e8e3e0fa7093cb4abb12c5f65d34c extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__4e83fcc8047a877726edf650070523fd');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->dataUrlFunction((isset($context['path']) || array_key_exists('path', $context) ? $context['path'] : (function () {
            throw new RuntimeError('Variable "path" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__4e83fcc8047a877726edf650070523fd');
    }

    public function getTemplateName()
    {
        return '__string_template__4e83fcc8047a877726edf650070523fd';
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
        return new Source('{{ dataUrl(path) }}', '__string_template__4e83fcc8047a877726edf650070523fd', '');
    }
}
