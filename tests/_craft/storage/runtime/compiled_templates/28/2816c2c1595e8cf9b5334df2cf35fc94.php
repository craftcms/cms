<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__9d0e7d9d7b62910fd04d04553b555c4c */
class __TwigTemplate_dffbfb28bdc4dea0016db083218e562d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__9d0e7d9d7b62910fd04d04553b555c4c');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->rssFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__9d0e7d9d7b62910fd04d04553b555c4c');
    }

    public function getTemplateName()
    {
        return '__string_template__9d0e7d9d7b62910fd04d04553b555c4c';
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
        return new Source('{{ d|rss }}', '__string_template__9d0e7d9d7b62910fd04d04553b555c4c', '');
    }
}
