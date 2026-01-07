<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/fallback-icon.svg.twig */
class __TwigTemplate_086793ece2f36a68357e7c3c169aefa7 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/fallback-icon.svg.twig');
        // line 1
        echo '<svg version="1.1" baseProfile="full" width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
    <title>';
        // line 2
        echo twig_escape_filter($this->env, (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
            throw new RuntimeError('Variable "label" does not exist.', 2, $this->source);
        })()), 'html', null, true);
        echo '</title>
    <circle cx="10" cy="10" r="10" fill="#000" fill-opacity="0.35"/>
    <text x="10" y="15" font-size="15" font-family="sans-serif" font-weight="bold" text-anchor="middle" fill="#000">';
        // line 4
        echo twig_escape_filter($this->env, twig_upper_filter($this->env, twig_slice($this->env, (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
            throw new RuntimeError('Variable "label" does not exist.', 4, $this->source);
        })()), 0, 1)), 'html', null, true);
        echo '</text>
</svg>
';
        craft\helpers\Template::endProfile('template', '_includes/fallback-icon.svg.twig');
    }

    public function getTemplateName()
    {
        return '_includes/fallback-icon.svg.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [46 => 4,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('<svg version="1.1" baseProfile="full" width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
    <title>{{ label }}</title>
    <circle cx="10" cy="10" r="10" fill="#000" fill-opacity="0.35"/>
    <text x="10" y="15" font-size="15" font-family="sans-serif" font-weight="bold" text-anchor="middle" fill="#000">{{ label[0:1]|upper }}</text>
</svg>
', '_includes/fallback-icon.svg.twig', '/Users/brianhanson/Development/craft5/src/templates/_includes/fallback-icon.svg.twig');
    }
}
