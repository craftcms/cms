<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* utilities/_index.twig */
class __TwigTemplate_8dc2370828f0cfc25e8cbc7c54604596 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'sidebar' => $this->block_sidebar(...),
            'toolbar' => $this->block_toolbar(...),
            'content' => $this->block_content(...),
            'footer' => $this->block_footer(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 1
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'utilities/_index.twig');
        // line 3
        $context['title'] = (isset($context['displayName']) || array_key_exists('displayName', $context) ? $context['displayName'] : (function () {
            throw new RuntimeError('Variable "displayName" does not exist.', 3, $this->source);
        })());
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'utilities/_index.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'utilities/_index.twig');
    }

    // line 5
    public function block_sidebar($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 6
        echo '    ';
        echo isset($context['sidebarHtml']) || array_key_exists('sidebarHtml', $context) ? $context['sidebarHtml'] : (function () {
            throw new RuntimeError('Variable "sidebarHtml" does not exist.', 6, $this->source);
        })();
        echo '
';
        craft\helpers\Template::endProfile('block', 'sidebar');
    }

    // line 9
    public function block_toolbar($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'toolbar');
        // line 10
        echo '    ';
        echo isset($context['toolbarHtml']) || array_key_exists('toolbarHtml', $context) ? $context['toolbarHtml'] : (function () {
            throw new RuntimeError('Variable "toolbarHtml" does not exist.', 10, $this->source);
        })();
        echo '
';
        craft\helpers\Template::endProfile('block', 'toolbar');
    }

    // line 13
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 14
        echo '    ';
        echo isset($context['contentHtml']) || array_key_exists('contentHtml', $context) ? $context['contentHtml'] : (function () {
            throw new RuntimeError('Variable "contentHtml" does not exist.', 14, $this->source);
        })();
        echo '
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    // line 17
    public function block_footer($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'footer');
        // line 18
        echo '    ';
        echo isset($context['footerHtml']) || array_key_exists('footerHtml', $context) ? $context['footerHtml'] : (function () {
            throw new RuntimeError('Variable "footerHtml" does not exist.', 18, $this->source);
        })();
        echo '
';
        craft\helpers\Template::endProfile('block', 'footer');
    }

    public function getTemplateName()
    {
        return 'utilities/_index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [98 => 18,  93 => 17,  85 => 14,  80 => 13,  72 => 10,  67 => 9,  59 => 6,  54 => 5,  48 => 1,  46 => 3,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('{% extends "_layouts/cp" %}

{% set title = displayName %}

{% block sidebar %}
    {{ sidebarHtml | raw }}
{% endblock %}

{% block toolbar %}
    {{ toolbarHtml|raw }}
{% endblock %}

{% block content %}
    {{ contentHtml|raw }}
{% endblock %}

{% block footer %}
    {{ footerHtml|raw }}
{% endblock %}
', 'utilities/_index.twig', '/Users/brianhanson/Development/craft5/src/templates/utilities/_index.twig');
    }
}
