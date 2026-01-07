<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/assets/volumes/_index.twig */
class __TwigTemplate_9bcfe9466747a28b531dbfa9a4a41190 extends Template
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
        // line 1
        return 'settings/assets/_layout';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'settings/assets/volumes/_index.twig');
        // line 2
        $context['selectedNavItem'] = 'volumes';
        // line 4
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 4, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\admintable\\AdminTableAsset'], 'method');
        // line 6
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 6, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Name', 1 => 'Handle', 2 => 'Type', 3 => 'No volumes exist yet.']], 'method');
        // line 21
        $context['tableData'] = [];
        // line 22
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['volumes']) || array_key_exists('volumes', $context) ? $context['volumes'] : (function () {
            throw new RuntimeError('Variable "volumes" does not exist.', 22, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['volume']) {
            // line 23
            $context['volumeIsMissing'] = false;
            // line 25
            if ($this->env->getTest('missing')->getCallable()($context['volume'])) {
                // line 26
                $context['volumeIsMissing'] = true;
            }
            // line 29
            $context['tableData'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
                throw new RuntimeError('Variable "tableData" does not exist.', 29, $this->source);
            })()), [0 => ['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 30
                $context['volume'], 'id', []), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 31
                    $context['volume'], 'name', []), 'site'), 'url' => craft\helpers\UrlHelper::url(('settings/assets/volumes/'.craft\helpers\Template::attribute($this->env, $this->source,             // line 32
                        $context['volume'], 'id', []))), 'name' => twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 33
                            $context['volume'], 'name', []), 'site')), 'handle' => craft\helpers\Template::attribute($this->env, $this->source,             // line 34
                                $context['volume'], 'handle', [])]]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['volume'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 38
        ob_start();
        // line 39
        echo "var columns = [
    { name: '__slot:title', title: Craft.t('app', 'Name') },
    { name: '__slot:handle', title: Craft.t('app', 'Handle') },
];

new Craft.VueAdminTable({
    columns: columns,
    container: '#volumes-vue-admin-table',
    deleteAction: 'volumes/delete-volume',
    emptyMessage: Craft.t('app', 'No volumes exist yet.'),
    reorderAction: '";
        // line 49
        echo (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['volumes']) || array_key_exists('volumes', $context) ? $context['volumes'] : (function () {
            throw new RuntimeError('Variable "volumes" does not exist.', 49, $this->source);
        })())) > 1)) ? ('volumes/reorder-volumes') : ('');
        echo "',
    tableData: ";
        // line 50
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
            throw new RuntimeError('Variable "tableData" does not exist.', 50, $this->source);
        })()));
        echo '
});
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 1
        $this->parent = $this->loadTemplate('settings/assets/_layout', 'settings/assets/volumes/_index.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/assets/volumes/_index.twig');
    }

    // line 13
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 14
        echo '    <div id="volumes-vue-admin-table"></div>

    <div class="buttons">
        <a class="btn submit add icon" href="';
        // line 17
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('settings/assets/volumes/new'), 'html', null, true);
        echo '">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New volume', 'app'), 'html', null, true);
        echo '</a>
    </div>
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/assets/volumes/_index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [113 => 17,  108 => 14,  103 => 13,  97 => 1,  91 => 50,  87 => 49,  75 => 39,  73 => 38,  67 => 34,  66 => 33,  65 => 32,  64 => 31,  63 => 30,  62 => 29,  59 => 26,  57 => 25,  55 => 23,  51 => 22,  49 => 21,  47 => 6,  45 => 4,  43 => 2,  35 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"settings/assets/_layout\" %}
{% set selectedNavItem = 'volumes' %}

{% do view.registerAssetBundle('craft\\\\web\\\\assets\\\\admintable\\\\AdminTableAsset') -%}

{% do view.registerTranslations('app', [
    \"Name\",
    \"Handle\",
    \"Type\",
    \"No volumes exist yet.\"
]) %}

{% block content %}
    <div id=\"volumes-vue-admin-table\"></div>

    <div class=\"buttons\">
        <a class=\"btn submit add icon\" href=\"{{ url('settings/assets/volumes/new') }}\">{{ \"New volume\"|t('app') }}</a>
    </div>
{% endblock %}

{% set tableData = [] %}
{% for volume in volumes %}
    {% set volumeIsMissing = false %}

    {% if volume is missing %}
        {% set volumeIsMissing = true %}
    {% endif %}

    {% set tableData = tableData|merge([{
        id: volume.id,
        title: volume.name|t('site'),
        url: url('settings/assets/volumes/' ~ volume.id),
        name: volume.name|t('site')|e,
        handle: volume.handle,
    }]) %}
{% endfor %}

{% js %}
var columns = [
    { name: '__slot:title', title: Craft.t('app', 'Name') },
    { name: '__slot:handle', title: Craft.t('app', 'Handle') },
];

new Craft.VueAdminTable({
    columns: columns,
    container: '#volumes-vue-admin-table',
    deleteAction: 'volumes/delete-volume',
    emptyMessage: Craft.t('app', 'No volumes exist yet.'),
    reorderAction: '{{ volumes|length > 1 ? 'volumes/reorder-volumes' : ''}}',
    tableData: {{ tableData|json_encode|raw }}
});
{% endjs %}
", 'settings/assets/volumes/_index.twig', '/Users/brianhanson/Development/craft5/src/templates/settings/assets/volumes/_index.twig');
    }
}
