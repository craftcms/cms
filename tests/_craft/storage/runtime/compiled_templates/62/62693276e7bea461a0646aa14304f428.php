<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/fields */
class __TwigTemplate_6706077e80f32c2f12e4abdceccc111d extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'actionButton' => $this->block_actionButton(...),
            'content' => $this->block_content(...),
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
        craft\helpers\Template::beginProfile('template', 'settings/fields');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Fields', 'app');
        // line 6
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 6, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\admintable\\AdminTableAsset'], 'method');
        // line 8
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 8, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Name', 1 => 'Handle', 2 => 'Type', 3 => 'This field’s values are used as search keywords.', 4 => 'No fields exist yet.', 5 => 'No results.']], 'method');
        // line 17
        $context['crumbs'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 21
        $context['emptyMessage'] = $this->extensions['craft\web\twig\Extension']->translateFilter('No fields exist yet.', 'app');
        // line 34
        ob_start();
        // line 35
        echo "    var columns = [
        { name: '__slot:title', title: Craft.t('app', 'Name') },
        {
            name: 'searchable',
            titleClass: 'thin',
            callback: value => {
                if (!value) {
                    return null;
                }
                return `<div class=\"badge-icon\" data-icon=\"search\" title=\"\${Craft.t('app', 'This field’s values are used as search keywords.')}\" aria-label=\"\${Craft.t('app', 'This field’s values are used as search keywords.')}\" role=\"img\"></div>`;
            }
        },
        ";
        // line 47
        if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 47, $this->source);
        })()), 'app', []), 'isMultiSite', [])) {
            // line 48
            echo "        {
            name: 'translatable',
            titleClass: 'thin',
            callback: value => {
                if (!value) {
                    return null;
                }
                return `<div class=\"badge-icon\" data-icon=\"language\" title=\"\${value}\" aria-label=\"\${value}\" role=\"img\"></div>`;
            }
        },
        ";
        }
        // line 59
        echo "        { name: '__slot:handle', title: Craft.t('app', 'Handle') },
        {
            name: 'type',
            title: Craft.t('app', 'Type'),
            callback: (value) => {
                let label = '<div class=\"flex flex-nowrap gap-s\">' +
                    `<div class=\"cp-icon small\">\${value.icon}</div>`;
                if (value.isMissing) {
                    label += `<span class=\"error\">\${value.label}</span>`;
                } else {
                    label += `<span>\${value.label}</span>`;
                }
                label += '</div>';
                return label;
            }
        },
    ];

    new Craft.VueAdminTable({
        columns: columns,
        container: '#fields-vue-admin-table',
        deleteAction: 'fields/delete-field',
        emptyMessage: Craft.t('app', '";
        // line 81
        echo twig_escape_filter($this->env, (isset($context['emptyMessage']) || array_key_exists('emptyMessage', $context) ? $context['emptyMessage'] : (function () {
            throw new RuntimeError('Variable "emptyMessage" does not exist.', 81, $this->source);
        })()), 'html', null, true);
        echo "'),
        tableDataEndpoint: 'fields/table-data',
        search: true,
    });
";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/fields', 3);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/fields');
    }

    // line 24
    public function block_actionButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 25
        echo '    ';
        $context['newFieldUrl'] = craft\helpers\UrlHelper::url('settings/fields/new');
        // line 26
        echo '    <a href="';
        echo twig_escape_filter($this->env, (isset($context['newFieldUrl']) || array_key_exists('newFieldUrl', $context) ? $context['newFieldUrl'] : (function () {
            throw new RuntimeError('Variable "newFieldUrl" does not exist.', 26, $this->source);
        })()), 'html', null, true);
        echo '" class="submit btn add icon">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New field', 'app'), 'html', null, true);
        echo '</a>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
    }

    // line 30
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 31
        echo '    <div id="fields-vue-admin-table"></div>
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/fields';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [148 => 31,  143 => 30,  133 => 26,  130 => 25,  125 => 24,  119 => 3,  111 => 81,  87 => 59,  74 => 48,  72 => 47,  58 => 35,  56 => 34,  54 => 21,  52 => 17,  50 => 8,  48 => 6,  46 => 4,  44 => 1,  36 => 3];
    }

    public function getSourceContext()
    {
        return new Source("{% requireAdmin %}

{% extends \"_layouts/cp\" %}
{% set title = \"Fields\"|t('app') %}

{% do view.registerAssetBundle('craft\\\\web\\\\assets\\\\admintable\\\\AdminTableAsset') -%}

{% do view.registerTranslations('app', [
    \"Name\",
    \"Handle\",
    \"Type\",
    \"This field’s values are used as search keywords.\",
    \"No fields exist yet.\",
    \"No results.\",
]) %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% set emptyMessage = \"No fields exist yet.\"|t('app') %}


{% block actionButton %}
    {% set newFieldUrl = url('settings/fields/new') %}
    <a href=\"{{ newFieldUrl }}\" class=\"submit btn add icon\">{{ \"New field\"|t('app') }}</a>
{% endblock %}


{% block content %}
    <div id=\"fields-vue-admin-table\"></div>
{% endblock %}

{% js %}
    var columns = [
        { name: '__slot:title', title: Craft.t('app', 'Name') },
        {
            name: 'searchable',
            titleClass: 'thin',
            callback: value => {
                if (!value) {
                    return null;
                }
                return `<div class=\"badge-icon\" data-icon=\"search\" title=\"\${Craft.t('app', 'This field’s values are used as search keywords.')}\" aria-label=\"\${Craft.t('app', 'This field’s values are used as search keywords.')}\" role=\"img\"></div>`;
            }
        },
        {% if craft.app.isMultiSite %}
        {
            name: 'translatable',
            titleClass: 'thin',
            callback: value => {
                if (!value) {
                    return null;
                }
                return `<div class=\"badge-icon\" data-icon=\"language\" title=\"\${value}\" aria-label=\"\${value}\" role=\"img\"></div>`;
            }
        },
        {% endif %}
        { name: '__slot:handle', title: Craft.t('app', 'Handle') },
        {
            name: 'type',
            title: Craft.t('app', 'Type'),
            callback: (value) => {
                let label = '<div class=\"flex flex-nowrap gap-s\">' +
                    `<div class=\"cp-icon small\">\${value.icon}</div>`;
                if (value.isMissing) {
                    label += `<span class=\"error\">\${value.label}</span>`;
                } else {
                    label += `<span>\${value.label}</span>`;
                }
                label += '</div>';
                return label;
            }
        },
    ];

    new Craft.VueAdminTable({
        columns: columns,
        container: '#fields-vue-admin-table',
        deleteAction: 'fields/delete-field',
        emptyMessage: Craft.t('app', '{{ emptyMessage }}'),
        tableDataEndpoint: 'fields/table-data',
        search: true,
    });
{% endjs %}
", 'settings/fields', '/Users/brianhanson/Development/craft5/src/templates/settings/fields/index.twig');
    }
}
