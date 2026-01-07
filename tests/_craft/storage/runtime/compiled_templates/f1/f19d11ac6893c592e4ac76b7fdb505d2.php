<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0ba5909c66cc1acef7426396a5643e48 */
class __TwigTemplate_97aec2cb4c2efa6a6138a32be6e9c003 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0ba5909c66cc1acef7426396a5643e48');
        // line 1
        echo 'testing-uri-longer-than-255-chars/';
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'slug', []));
        craft\helpers\Template::endProfile('template', '__string_template__0ba5909c66cc1acef7426396a5643e48');
    }

    public function getTemplateName()
    {
        return '__string_template__0ba5909c66cc1acef7426396a5643e48';
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
        return new Source('testing-uri-longer-than-255-chars/{{ (_variables.slug ?? object.slug)|raw }}', '__string_template__0ba5909c66cc1acef7426396a5643e48', '');
    }
}
