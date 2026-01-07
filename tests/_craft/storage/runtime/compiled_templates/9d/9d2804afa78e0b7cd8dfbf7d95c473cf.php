<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0497e0dac47077b51f7bb366ec315a70 */
class __TwigTemplate_5313fc61ef3d2cc8c18a858c2d1ecd51 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0497e0dac47077b51f7bb366ec315a70');
        // line 1
        echo 'test/';
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'slug', []));
        craft\helpers\Template::endProfile('template', '__string_template__0497e0dac47077b51f7bb366ec315a70');
    }

    public function getTemplateName()
    {
        return '__string_template__0497e0dac47077b51f7bb366ec315a70';
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
        return new Source('test/{{ (_variables.slug ?? object.slug)|raw }}', '__string_template__0497e0dac47077b51f7bb366ec315a70', '');
    }
}
