<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__28e2acdb19e26ef527d97f2c64d43665 */
class __TwigTemplate_b0bfb0c69dbe5aeaca6e3bf9cdccc245 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__28e2acdb19e26ef527d97f2c64d43665');
        // line 1
        echo 'some-uri/';
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'slug', []));
        craft\helpers\Template::endProfile('template', '__string_template__28e2acdb19e26ef527d97f2c64d43665');
    }

    public function getTemplateName()
    {
        return '__string_template__28e2acdb19e26ef527d97f2c64d43665';
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
        return new Source('some-uri/{{ (_variables.slug ?? object.slug)|raw }}', '__string_template__28e2acdb19e26ef527d97f2c64d43665', '');
    }
}
