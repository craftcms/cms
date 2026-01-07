<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/assets/volumes/_index.twig */
class __TwigTemplate_52117a4a3deaab0efd9ca2b40d7647aa extends Template
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
        // line 1
        return 'settings/assets/_layout';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'settings/assets/volumes/_index.twig');
        // line 2
        $context['selectedNavItem'] = 'volumes';
        // line 4
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 4, $this->source);
        })()), 'registerAssetBundle', ['craft\\web\\assets\\admintable\\AdminTableAsset'], 'method', false, false, false, 4);
        // line 6
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 6, $this->source);
        })()), 'registerTranslations', ['app', ['Name', 'Handle', 'Type', 'No volumes exist yet.']], 'method', false, false, false, 6);
        // line 21
        $context['tableData'] = [];
        // line 22
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['volumes']) || array_key_exists('volumes', $context) ? $context['volumes'] : (function () {
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
            })()), [['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 30
                $context['volume'], 'id', [], 'any', false, false, false, 30), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 31
                    $context['volume'], 'name', [], 'any', false, false, false, 31), 'site'), 'url' => craft\helpers\UrlHelper::url(('settings/assets/volumes/'.craft\helpers\Template::attribute($this->env, $this->source,             // line 32
                        $context['volume'], 'id', [], 'any', false, false, false, 32))), 'name' => $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 33
                            $context['volume'], 'name', [], 'any', false, false, false, 33), 'site')), 'handle' => craft\helpers\Template::attribute($this->env, $this->source,             // line 34
                                $context['volume'], 'handle', [], 'any', false, false, false, 34)]]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['volume'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 38
        ob_start();
        // line 39
        yield "var columns = [
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
        yield (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['volumes']) || array_key_exists('volumes', $context) ? $context['volumes'] : (function () {
            throw new RuntimeError('Variable "volumes" does not exist.', 49, $this->source);
        })())) > 1)) ? ('volumes/reorder-volumes') : ('');
        yield "',
    tableData: ";
        // line 50
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
            throw new RuntimeError('Variable "tableData" does not exist.', 50, $this->source);
        })()));
        yield '
});
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 1
        $this->parent = $this->loadTemplate('settings/assets/_layout', 'settings/assets/volumes/_index.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/assets/volumes/_index.twig');
    }

    // line 13
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 14
        yield '    <div id="volumes-vue-admin-table"></div>

    <div class="buttons">
        <a class="btn submit add icon" href="';
        // line 17
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('settings/assets/volumes/new'), 'html', null, true);
        yield '">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New volume', 'app'), 'html', null, true);
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
        return 'settings/assets/volumes/_index.twig';
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
        return [121 => 17,  116 => 14,  108 => 13,  102 => 1,  96 => 50,  92 => 49,  80 => 39,  78 => 38,  72 => 34,  71 => 33,  70 => 32,  69 => 31,  68 => 30,  67 => 29,  64 => 26,  62 => 25,  60 => 23,  56 => 22,  54 => 21,  52 => 6,  50 => 4,  48 => 2,  40 => 1];
    }

    public function getSourceContext(): Source
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
", 'settings/assets/volumes/_index.twig', '/tmp/packages/craft5/src/templates/settings/assets/volumes/_index.twig');
    }
}
