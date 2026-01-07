<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__d42d27c04c8ec5285d52cb2f0d459f4c */
class __TwigTemplate_f34831dec87064d2fe6d94c9e8bbcb78 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__d42d27c04c8ec5285d52cb2f0d459f4c');
        // line 1
        echo twig_join_filter(twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->groupFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'users', [], 'method'), 'id', [0 => 1], 'method'), 'all', [], 'method'), 'username')), ',');
        craft\helpers\Template::endProfile('template', '__string_template__d42d27c04c8ec5285d52cb2f0d459f4c');
    }

    public function getTemplateName()
    {
        return '__string_template__d42d27c04c8ec5285d52cb2f0d459f4c';
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
        return new Source('{{ craft.users().id(1).all()|group("username")|keys|join(",") }}', '__string_template__d42d27c04c8ec5285d52cb2f0d459f4c', '');
    }
}
