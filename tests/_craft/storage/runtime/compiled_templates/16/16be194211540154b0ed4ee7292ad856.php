<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/sections/_index.twig */
class __TwigTemplate_271728058b973263003f008943e25d11 extends Template
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
        // line 1
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'settings/sections/_index.twig');
        // line 2
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Sections', 'app');
        // line 4
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 4, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\admintable\\AdminTableAsset'], 'method');
        // line 6
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 6, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Are you sure you want to delete “{name}” and all its entries?', 1 => 'Edit entry type', 2 => 'Edit entry types ({count})', 3 => 'Edit entry types', 4 => 'Entry Types', 5 => 'Handle', 6 => 'Name', 7 => 'No sections exist yet.', 8 => 'Type']], 'method');
        // line 18
        $context['crumbs'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 31
        $context['tableData'] = [];
        // line 32
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['sections']) || array_key_exists('sections', $context) ? $context['sections'] : (function () {
            throw new RuntimeError('Variable "sections" does not exist.', 32, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['section']) {
            // line 33
            $context['isSingle'] = ((craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'type', []) == 'single') && ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'getEntryTypes', [], 'method')) == 1));
            // line 35
            $context['tableData'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
                throw new RuntimeError('Variable "tableData" does not exist.', 35, $this->source);
            })()), [0 => ['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 36
                $context['section'], 'id', []), 'name' => twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 37
                    $context['section'], 'name', []), 'site')), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 38
                        $context['section'], 'name', []), 'site'), 'url' => craft\helpers\UrlHelper::url(('settings/sections/'.craft\helpers\Template::attribute($this->env, $this->source,             // line 39
                            $context['section'], 'id', []))), 'handle' => craft\helpers\Template::attribute($this->env, $this->source,             // line 40
                                $context['section'], 'handle', []), 'type' => twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(twig_title_string_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source,             // line 41
                                    $context['section'], 'type', [])), 'app'))]]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['section'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 45
        ob_start();
        // line 46
        echo "    var columns = [
        { name: '__slot:title', title: Craft.t('app', 'Name') },
        { name: '__slot:handle', title: Craft.t('app', 'Handle') },
        { name: 'type', title: Craft.t('app', 'Type') },
    ];

    new Craft.VueAdminTable({
        columns: columns,
        container: '#sections-vue-admin-table',
        deleteAction: 'sections/delete-section',
        deleteConfirmationMessage: Craft.t('app', \"Are you sure you want to delete “{name}” and all its entries?\"),
        emptyMessage: Craft.t('app', 'No sections exist yet.'),
        tableData: ";
        // line 58
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
            throw new RuntimeError('Variable "tableData" does not exist.', 58, $this->source);
        })()));
        echo '
    });
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/sections/_index.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/sections/_index.twig');
    }

    // line 22
    public function block_actionButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 23
        echo '    <a href="';
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('settings/sections/new'), 'html', null, true);
        echo '" class="btn submit add icon">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New section', 'app'), 'html', null, true);
        echo '</a>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
    }

    // line 27
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 28
        echo '    <div id="sections-vue-admin-table"></div>
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/sections/_index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [120 => 28,  115 => 27,  105 => 23,  100 => 22,  94 => 1,  88 => 58,  74 => 46,  72 => 45,  66 => 41,  65 => 40,  64 => 39,  63 => 38,  62 => 37,  61 => 36,  60 => 35,  58 => 33,  54 => 32,  52 => 31,  50 => 18,  48 => 6,  46 => 4,  44 => 2,  36 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"_layouts/cp\" %}
{% set title = \"Sections\"|t('app') %}

{% do view.registerAssetBundle('craft\\\\web\\\\assets\\\\admintable\\\\AdminTableAsset') -%}

{% do view.registerTranslations('app', [
    \"Are you sure you want to delete “{name}” and all its entries?\",
    \"Edit entry type\",
    \"Edit entry types ({count})\",
    \"Edit entry types\",
    \"Entry Types\",
    \"Handle\",
    \"Name\",
    \"No sections exist yet.\",
    \"Type\",
]) %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% block actionButton %}
    <a href=\"{{ url('settings/sections/new') }}\" class=\"btn submit add icon\">{{ \"New section\"|t('app') }}</a>
{% endblock %}


{% block content %}
    <div id=\"sections-vue-admin-table\"></div>
{% endblock %}

{% set tableData = [] %}
{% for section in sections %}
    {% set isSingle = section.type == 'single' and section.getEntryTypes()|length == 1 %}

    {% set tableData = tableData|merge([{
        id: section.id,
        name: section.name|t('site')|e,
        title: section.name|t('site'),
        url: url('settings/sections/' ~ section.id),
        handle: section.handle,
        type: section.type|title|t('app')|e,
    }]) %}
{% endfor %}

{% js %}
    var columns = [
        { name: '__slot:title', title: Craft.t('app', 'Name') },
        { name: '__slot:handle', title: Craft.t('app', 'Handle') },
        { name: 'type', title: Craft.t('app', 'Type') },
    ];

    new Craft.VueAdminTable({
        columns: columns,
        container: '#sections-vue-admin-table',
        deleteAction: 'sections/delete-section',
        deleteConfirmationMessage: Craft.t('app', \"Are you sure you want to delete “{name}” and all its entries?\"),
        emptyMessage: Craft.t('app', 'No sections exist yet.'),
        tableData: {{ tableData|json_encode|raw }}
    });
{% endjs %}
", 'settings/sections/_index.twig', '/Users/brianhanson/Development/craft5/src/templates/settings/sections/_index.twig');
    }
}
