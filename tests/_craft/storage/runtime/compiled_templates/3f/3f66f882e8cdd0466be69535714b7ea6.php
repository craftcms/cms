<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__297b84b057d19eda851899e028cdfa88 */
class __TwigTemplate_0e152ce6d7560c9125ae2679e69bb58f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__297b84b057d19eda851899e028cdfa88');
        // line 1
        echo 'different-uri/';
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'slug', []));
        craft\helpers\Template::endProfile('template', '__string_template__297b84b057d19eda851899e028cdfa88');
    }

    public function getTemplateName()
    {
        return '__string_template__297b84b057d19eda851899e028cdfa88';
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
        return new Source('different-uri/{{ (_variables.slug ?? object.slug)|raw }}', '__string_template__297b84b057d19eda851899e028cdfa88', '');
    }
}
