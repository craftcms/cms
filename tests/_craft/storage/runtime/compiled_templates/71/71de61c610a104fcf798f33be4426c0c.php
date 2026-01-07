<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/users/_layout */
class __TwigTemplate_3350a330019a89c89a73cae2298cc293 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'sidebar' => $this->block_sidebar(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 3
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'settings/users/_layout');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('User Settings', 'app');
        // line 6
        $context['crumbs'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 10
        $context['navItems'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['groups' => (((        // line 11
            (isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
                throw new RuntimeError('Variable "CraftEdition" does not exist.', 11, $this->source);
            })()) == (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
                throw new RuntimeError('Variable "CraftPro" does not exist.', 11, $this->source);
            })()))) ? (['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Groups', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/users')]) : ('')), 'fields' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Profile Fields', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/users/fields')], 'settings' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/users/settings')]]);
        // line 16
        $context['docTitle'] = ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['navItems']) || array_key_exists('navItems', $context) ? $context['navItems'] : (function () {
            throw new RuntimeError('Variable "navItems" does not exist.', 16, $this->source);
        })()), (isset($context['selectedNavItem']) || array_key_exists('selectedNavItem', $context) ? $context['selectedNavItem'] : (function () {
            throw new RuntimeError('Variable "selectedNavItem" does not exist.', 16, $this->source);
        })()), [], 'array'), 'label', []).' - ').(isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
            throw new RuntimeError('Variable "title" does not exist.', 16, $this->source);
        })()));
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/users/_layout', 3);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/users/_layout');
    }

    // line 18
    public function block_sidebar($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 19
        echo '    ';
        $this->loadTemplate('_includes/nav', 'settings/users/_layout', 19)->display(twig_to_array(['label' =>         // line 21
(isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
    throw new RuntimeError('Variable "title" does not exist.', 21, $this->source);
})()), 'items' =>         // line 22
(isset($context['navItems']) || array_key_exists('navItems', $context) ? $context['navItems'] : (function () {
    throw new RuntimeError('Variable "navItems" does not exist.', 22, $this->source);
})()), 'selectedItem' =>         // line 23
(isset($context['selectedNavItem']) || array_key_exists('selectedNavItem', $context) ? $context['selectedNavItem'] : (function () {
    throw new RuntimeError('Variable "selectedNavItem" does not exist.', 23, $this->source);
})()), ]));
        craft\helpers\Template::endProfile('block', 'sidebar');
    }

    public function getTemplateName()
    {
        return 'settings/users/_layout';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [69 => 23,  68 => 22,  67 => 21,  65 => 19,  60 => 18,  54 => 3,  52 => 16,  50 => 11,  49 => 10,  47 => 6,  45 => 4,  43 => 1,  35 => 3];
    }

    public function getSourceContext()
    {
        return new Source("{% requireAdmin %}

{% extends \"_layouts/cp\" %}
{% set title = \"User Settings\"|t('app') %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% set navItems = {
    groups: CraftEdition == CraftPro ? { label: \"User Groups\"|t('app'), url: url('settings/users') },
    fields: { label: \"User Profile Fields\"|t('app'), url: url('settings/users/fields') },
    settings: { label: \"Settings\"|t('app'), url: url('settings/users/settings') }
}|filter %}

{% set docTitle = navItems[selectedNavItem].label~' - '~title %}

{% block sidebar %}
    {% include \"_includes/nav\"
        with {
            label: title,
            items: navItems,
            selectedItem: selectedNavItem,
        }
        only
    %}
{% endblock %}
", 'settings/users/_layout', '/Users/brianhanson/Development/craft5/src/templates/settings/users/_layout.twig');
    }
}
