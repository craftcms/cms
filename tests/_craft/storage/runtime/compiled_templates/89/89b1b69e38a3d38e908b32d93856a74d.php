<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* settings/general/_images/icon.twig */
class __TwigTemplate_f246c2909bb4d466f26e577618eda098 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'changeIconLabel' => $this->block_changeIconLabel(...),
            'deleteIconLabel' => $this->block_deleteIconLabel(...),
            'uploadIconLabel' => $this->block_uploadIconLabel(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 1
        return 'settings/general/_images/image';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'settings/general/_images/icon.twig');
        // line 3
        $context['imageType'] = 'icon';
        // line 1
        $this->parent = $this->loadTemplate('settings/general/_images/image', 'settings/general/_images/icon.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/general/_images/icon.twig');
    }

    // line 5
    public function block_changeIconLabel($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'changeIconLabel');
        // line 6
        echo '    ';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Change icon', 'app'), 'html', null, true);
        echo '
';
        craft\helpers\Template::endProfile('block', 'changeIconLabel');
    }

    // line 9
    public function block_deleteIconLabel($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'deleteIconLabel');
        // line 10
        echo '    ';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Delete icon', 'app'), 'html', null, true);
        echo '
';
        craft\helpers\Template::endProfile('block', 'deleteIconLabel');
    }

    // line 13
    public function block_uploadIconLabel($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'uploadIconLabel');
        // line 14
        echo '    ';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Upload icon', 'app'), 'html', null, true);
        echo '
';
        craft\helpers\Template::endProfile('block', 'uploadIconLabel');
    }

    public function getTemplateName()
    {
        return 'settings/general/_images/icon.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [84 => 14,  79 => 13,  71 => 10,  66 => 9,  58 => 6,  53 => 5,  47 => 1,  45 => 3,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"settings/general/_images/image\" %}

{% set imageType = \"icon\" %}

{% block changeIconLabel %}
    {{ 'Change icon'|t('app') }}
{% endblock %}

{% block deleteIconLabel %}
    {{ 'Delete icon'|t('app') }}
{% endblock %}

{% block uploadIconLabel %}
    {{ 'Upload icon'|t('app') }}
{% endblock %}
", 'settings/general/_images/icon.twig', '/Users/brianhanson/Development/craft5/src/templates/settings/general/_images/icon.twig');
    }
}
