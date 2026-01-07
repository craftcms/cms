<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* settings/general/_images/logo.twig */
class __TwigTemplate_6dcb7eba0f51c1e3fbe53bd3cc63ee00 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'changeLogoLabel' => $this->block_changeLogoLabel(...),
            'deleteLogoLabel' => $this->block_deleteLogoLabel(...),
            'uploadLogoLabel' => $this->block_uploadLogoLabel(...),
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
        craft\helpers\Template::beginProfile('template', 'settings/general/_images/logo.twig');
        // line 3
        $context['imageType'] = 'logo';
        // line 1
        $this->parent = $this->loadTemplate('settings/general/_images/image', 'settings/general/_images/logo.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/general/_images/logo.twig');
    }

    // line 5
    public function block_changeLogoLabel($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'changeLogoLabel');
        // line 6
        echo '    ';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Change logo', 'app'), 'html', null, true);
        echo '
';
        craft\helpers\Template::endProfile('block', 'changeLogoLabel');
    }

    // line 9
    public function block_deleteLogoLabel($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'deleteLogoLabel');
        // line 10
        echo '    ';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Delete logo', 'app'), 'html', null, true);
        echo '
';
        craft\helpers\Template::endProfile('block', 'deleteLogoLabel');
    }

    // line 13
    public function block_uploadLogoLabel($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'uploadLogoLabel');
        // line 14
        echo '    ';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Upload logo', 'app'), 'html', null, true);
        echo '
';
        craft\helpers\Template::endProfile('block', 'uploadLogoLabel');
    }

    public function getTemplateName()
    {
        return 'settings/general/_images/logo.twig';
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

{% set imageType = \"logo\" %}

{% block changeLogoLabel %}
    {{ 'Change logo'|t('app') }}
{% endblock %}

{% block deleteLogoLabel %}
    {{ 'Delete logo'|t('app') }}
{% endblock %}

{% block uploadLogoLabel %}
    {{ 'Upload logo'|t('app') }}
{% endblock %}
", 'settings/general/_images/logo.twig', '/Users/brianhanson/Development/craft5/src/templates/settings/general/_images/logo.twig');
    }
}
