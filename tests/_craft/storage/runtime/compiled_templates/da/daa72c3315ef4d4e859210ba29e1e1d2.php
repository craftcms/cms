<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/users/_layout */
class __TwigTemplate_adf601991505d86cbb2cfa4e41cbec84 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'sidebar' => $this->block_sidebar(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 3
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'settings/users/_layout');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('User Settings', 'app');
        // line 6
        $context['crumbs'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 10
        $context['navItems'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['teamGroup' => (((        // line 11
            (isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
                throw new RuntimeError('Variable "CraftEdition" does not exist.', 11, $this->source);
            })()) == (isset($context['CraftTeam']) || array_key_exists('CraftTeam', $context) ? $context['CraftTeam'] : (function () {
                throw new RuntimeError('Variable "CraftTeam" does not exist.', 11, $this->source);
            })()))) ? (['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Permissions', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/users')]) : ('')), 'groups' => (((        // line 12
                (isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
                    throw new RuntimeError('Variable "CraftEdition" does not exist.', 12, $this->source);
                })()) >= (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
                    throw new RuntimeError('Variable "CraftPro" does not exist.', 12, $this->source);
                })()))) ? (['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Groups', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/users')]) : ('')), 'fields' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Profile Fields', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/users/fields')], 'settings' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/users/settings')]]);
        // line 17
        $context['docTitle'] = ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['navItems']) || array_key_exists('navItems', $context) ? $context['navItems'] : (function () {
            throw new RuntimeError('Variable "navItems" does not exist.', 17, $this->source);
        })()), (isset($context['selectedNavItem']) || array_key_exists('selectedNavItem', $context) ? $context['selectedNavItem'] : (function () {
            throw new RuntimeError('Variable "selectedNavItem" does not exist.', 17, $this->source);
        })()), [], 'array', false, false, false, 17), 'label', [], 'any', false, false, false, 17).' - ').(isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
            throw new RuntimeError('Variable "title" does not exist.', 17, $this->source);
        })()));
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/users/_layout', 3);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/users/_layout');
    }

    // line 19
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_sidebar(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 20
        yield '    ';
        yield from $this->loadTemplate('_includes/nav', 'settings/users/_layout', 20)->unwrap()->yield(CoreExtension::toArray(['label' =>         // line 22
(isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
    throw new RuntimeError('Variable "title" does not exist.', 22, $this->source);
})()), 'items' =>         // line 23
(isset($context['navItems']) || array_key_exists('navItems', $context) ? $context['navItems'] : (function () {
    throw new RuntimeError('Variable "navItems" does not exist.', 23, $this->source);
})()), 'selectedItem' =>         // line 24
(isset($context['selectedNavItem']) || array_key_exists('selectedNavItem', $context) ? $context['selectedNavItem'] : (function () {
    throw new RuntimeError('Variable "selectedNavItem" does not exist.', 24, $this->source);
})())]));
        craft\helpers\Template::endProfile('block', 'sidebar');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/users/_layout';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return [78 => 24,  77 => 23,  76 => 22,  74 => 20,  66 => 19,  60 => 3,  58 => 17,  56 => 12,  55 => 11,  54 => 10,  52 => 6,  50 => 4,  48 => 1,  40 => 3];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% requireAdmin %}

{% extends \"_layouts/cp\" %}
{% set title = \"User Settings\"|t('app') %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% set navItems = {
    teamGroup: CraftEdition == CraftTeam ? { label: \"User Permissions\"|t('app'), url: url('settings/users') },
    groups: CraftEdition >= CraftPro ? { label: \"User Groups\"|t('app'), url: url('settings/users') },
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
", 'settings/users/_layout', '/tmp/packages/craft5/src/templates/settings/users/_layout.twig');
    }
}
