<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* withvar */
class __TwigTemplate_05338ef57376747c9c0908f73318c67b extends Template
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
        craft\helpers\Template::beginProfile('template', 'withvar');
        // line 1
        echo 'Hello iam ';
        echo twig_escape_filter($this->env, (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
            throw new RuntimeError('Variable "name" does not exist.', 1, $this->source);
        })()), 'html', null, true);
        craft\helpers\Template::endProfile('template', 'withvar');
    }

    public function getTemplateName()
    {
        return 'withvar';
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
        return new Source('Hello iam {{ name }}', 'withvar', '/Users/brianhanson/Development/craft5/tests/_craft/templates/withvar.twig');
    }
}
