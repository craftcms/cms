<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/users/groups/_index */
class __TwigTemplate_159d3e0cb9964d1765c88b32bab4e708 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 4
        return 'settings/users/_layout';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'settings/users/groups/_index');
        // line 1
        if (\Craft::$app->getEdition() < (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 1, $this->source);
        })())) {
            throw new yii\web\NotFoundHttpException;
        }
        // line 2
        Craft::$app->controller->requireAdmin();
        // line 5
        $context['selectedNavItem'] = 'groups';
        // line 7
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 7, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\admintable\\AdminTableAsset'], 'method');
        // line 9
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 9, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Name', 1 => 'Handle', 2 => 'No groups exist yet.']], 'method');
        // line 15
        $context['groups'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'app', []), 'userGroups', []), 'getAllGroups', [], 'method');
        // line 25
        $context['tableData'] = [];
        // line 26
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['groups']) || array_key_exists('groups', $context) ? $context['groups'] : (function () {
            throw new RuntimeError('Variable "groups" does not exist.', 26, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['group']) {
            // line 27
            $context['tableData'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
                throw new RuntimeError('Variable "tableData" does not exist.', 27, $this->source);
            })()), [0 => ['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 28
                $context['group'], 'id', []), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 29
                    $context['group'], 'name', []), 'site'), 'url' => craft\helpers\UrlHelper::url(('settings/users/groups/'.craft\helpers\Template::attribute($this->env, $this->source,             // line 30
                        $context['group'], 'id', []))), 'name' => twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 31
                            $context['group'], 'name', []), 'site')), 'handle' => craft\helpers\Template::attribute($this->env, $this->source,             // line 32
                                $context['group'], 'handle', [])]]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['group'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 36
        ob_start();
        // line 37
        echo "    var columns = [
        { name: '__slot:title', title: Craft.t('app', 'Name') },
        { name: '__slot:handle', title: Craft.t('app', 'Handle') }
    ];

    new Craft.VueAdminTable({
        columns: columns,
        container: '#groups-vue-admin-table',
        deleteAction: 'user-settings/delete-group',
        emptyMessage: Craft.t('app', 'No groups exist yet.'),
        tableData: ";
        // line 47
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
            throw new RuntimeError('Variable "tableData" does not exist.', 47, $this->source);
        })()));
        echo '
    });
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 4
        $this->parent = $this->loadTemplate('settings/users/_layout', 'settings/users/groups/_index', 4);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/users/groups/_index');
    }

    // line 17
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 18
        echo '    <div id="groups-vue-admin-table"></div>

    <div class="buttons">
        <a href="';
        // line 21
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('settings/users/groups/new'), 'html', null, true);
        echo '" class="btn submit add icon">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New user group', 'app'), 'html', null, true);
        echo '</a>
    </div>
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/users/groups/_index';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [111 => 21,  106 => 18,  101 => 17,  95 => 4,  89 => 47,  77 => 37,  75 => 36,  69 => 32,  68 => 31,  67 => 30,  66 => 29,  65 => 28,  64 => 27,  60 => 26,  58 => 25,  56 => 15,  54 => 9,  52 => 7,  50 => 5,  48 => 2,  43 => 1,  35 => 4];
    }

    public function getSourceContext()
    {
        return new Source("{% requireEdition CraftPro %}
{% requireAdmin %}

{% extends \"settings/users/_layout\" %}
{% set selectedNavItem = 'groups' %}

{% do view.registerAssetBundle('craft\\\\web\\\\assets\\\\admintable\\\\AdminTableAsset') -%}

{% do view.registerTranslations('app', [
    \"Name\",
    \"Handle\",
    \"No groups exist yet.\",
]) %}

{% set groups = craft.app.userGroups.getAllGroups() %}

{% block content %}
    <div id=\"groups-vue-admin-table\"></div>

    <div class=\"buttons\">
        <a href=\"{{ url('settings/users/groups/new') }}\" class=\"btn submit add icon\">{{ \"New user group\"|t('app') }}</a>
    </div>
{% endblock %}

{% set tableData = [] %}
{% for group in groups %}
    {% set tableData = tableData|merge([{
        id: group.id,
        title: group.name|t('site'),
        url: url('settings/users/groups/' ~ group.id),
        name: group.name|t('site')|e,
        handle: group.handle,
    }]) %}
{% endfor %}

{% js %}
    var columns = [
        { name: '__slot:title', title: Craft.t('app', 'Name') },
        { name: '__slot:handle', title: Craft.t('app', 'Handle') }
    ];

    new Craft.VueAdminTable({
        columns: columns,
        container: '#groups-vue-admin-table',
        deleteAction: 'user-settings/delete-group',
        emptyMessage: Craft.t('app', 'No groups exist yet.'),
        tableData: {{ tableData|json_encode|raw }}
    });
{% endjs %}", 'settings/users/groups/_index', '/Users/brianhanson/Development/craft5/src/templates/settings/users/groups/_index.twig');
    }
}
