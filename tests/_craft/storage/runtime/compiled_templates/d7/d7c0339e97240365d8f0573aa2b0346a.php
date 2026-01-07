<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/users/groups/_index */
class __TwigTemplate_ef490ad82265156826598c671c7ceb80 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 4
        return 'settings/users/_layout';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'settings/users/groups/_index');
        // line 1
        if (\Craft::$app->edition->value < (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
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
        })()), 'registerAssetBundle', ['craft\\web\\assets\\admintable\\AdminTableAsset'], 'method', false, false, false, 7);
        // line 9
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 9, $this->source);
        })()), 'registerTranslations', ['app', ['Name', 'Handle', 'No groups exist yet.']], 'method', false, false, false, 9);
        // line 15
        $context['groups'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'app', [], 'any', false, false, false, 15), 'userGroups', [], 'any', false, false, false, 15), 'getAllGroups', [], 'method', false, false, false, 15);
        // line 25
        $context['tableData'] = [];
        // line 26
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['groups']) || array_key_exists('groups', $context) ? $context['groups'] : (function () {
            throw new RuntimeError('Variable "groups" does not exist.', 26, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['group']) {
            // line 27
            $context['tableData'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
                throw new RuntimeError('Variable "tableData" does not exist.', 27, $this->source);
            })()), [['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 28
                $context['group'], 'id', [], 'any', false, false, false, 28), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 29
                    $context['group'], 'name', [], 'any', false, false, false, 29), 'site'), 'url' => craft\helpers\UrlHelper::url(('settings/users/groups/'.craft\helpers\Template::attribute($this->env, $this->source,             // line 30
                        $context['group'], 'id', [], 'any', false, false, false, 30))), 'name' => $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 31
                            $context['group'], 'name', [], 'any', false, false, false, 31), 'site')), 'handle' => craft\helpers\Template::attribute($this->env, $this->source,             // line 32
                                $context['group'], 'handle', [], 'any', false, false, false, 32)]]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['group'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 36
        ob_start();
        // line 37
        yield "    var columns = [
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
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
            throw new RuntimeError('Variable "tableData" does not exist.', 47, $this->source);
        })()));
        yield '
    });
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 4
        $this->parent = $this->loadTemplate('settings/users/_layout', 'settings/users/groups/_index', 4);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/users/groups/_index');
    }

    // line 17
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 18
        yield '    <div id="groups-vue-admin-table"></div>

    <div class="buttons">
        <a href="';
        // line 21
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('settings/users/groups/new'), 'html', null, true);
        yield '" class="btn submit add icon">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New user group', 'app'), 'html', null, true);
        yield '</a>
    </div>
';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/users/groups/_index';
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
        return [119 => 21,  114 => 18,  106 => 17,  100 => 4,  94 => 47,  82 => 37,  80 => 36,  74 => 32,  73 => 31,  72 => 30,  71 => 29,  70 => 28,  69 => 27,  65 => 26,  63 => 25,  61 => 15,  59 => 9,  57 => 7,  55 => 5,  53 => 2,  48 => 1,  40 => 4];
    }

    public function getSourceContext(): Source
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
{% endjs %}", 'settings/users/groups/_index', '/tmp/packages/craft5/src/templates/settings/users/groups/_index.twig');
    }
}
