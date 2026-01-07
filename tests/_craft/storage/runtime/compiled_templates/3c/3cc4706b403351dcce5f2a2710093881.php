<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cc6911df81e7cec0f02f1c0da0e85c94 */
class __TwigTemplate_1d1418c1f12184f79069847693906d8d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cc6911df81e7cec0f02f1c0da0e85c94');
        // line 1
        echo $context['exampleParam'] ?? null;
        echo craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'exampleParam', []);
        craft\helpers\Template::endProfile('template', '__string_template__cc6911df81e7cec0f02f1c0da0e85c94');
    }

    public function getTemplateName()
    {
        return '__string_template__cc6911df81e7cec0f02f1c0da0e85c94';
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
        return new Source('{{ exampleParam }}{{ object.exampleParam }}', '__string_template__cc6911df81e7cec0f02f1c0da0e85c94', '');
    }
}
