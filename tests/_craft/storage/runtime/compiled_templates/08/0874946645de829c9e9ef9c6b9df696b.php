<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/sections/_index.twig */
class __TwigTemplate_c153d69933cd1a44abd30cec56367fc3 extends Template
{
    private readonly Source $source;

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
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'settings/sections/_index.twig');
        // line 2
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Sections', 'app');
        // line 4
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 4, $this->source);
        })()), 'registerAssetBundle', ['craft\\web\\assets\\admintable\\AdminTableAsset'], 'method', false, false, false, 4);
        // line 6
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 6, $this->source);
        })()), 'registerTranslations', ['app', ['Are you sure you want to delete “{name}” and all its entries?', 'Edit entry type', 'Edit entry types ({count})', 'Edit entry types', 'Entry Types', 'Handle', 'Name', 'No sections exist yet.', 'Type']], 'method', false, false, false, 6);
        // line 18
        $context['crumbs'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 31
        $context['tableData'] = [];
        // line 32
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['sections']) || array_key_exists('sections', $context) ? $context['sections'] : (function () {
            throw new RuntimeError('Variable "sections" does not exist.', 32, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['section']) {
            // line 33
            $context['isSingle'] = ((craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'type', [], 'any', false, false, false, 33) == 'single') && ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'getEntryTypes', [], 'method', false, false, false, 33)) == 1));
            // line 35
            $context['tableData'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
                throw new RuntimeError('Variable "tableData" does not exist.', 35, $this->source);
            })()), [['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 36
                $context['section'], 'id', [], 'any', false, false, false, 36), 'name' => $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 37
                    $context['section'], 'name', [], 'any', false, false, false, 37), 'site')), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 38
                        $context['section'], 'name', [], 'any', false, false, false, 38), 'site'), 'url' => craft\helpers\UrlHelper::url(('settings/sections/'.craft\helpers\Template::attribute($this->env, $this->source,             // line 39
                            $context['section'], 'id', [], 'any', false, false, false, 39))), 'handle' => craft\helpers\Template::attribute($this->env, $this->source,             // line 40
                                $context['section'], 'handle', [], 'any', false, false, false, 40), 'type' => $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(Twig\Extension\CoreExtension::titleCase($this->env->getCharset(), craft\helpers\Template::attribute($this->env, $this->source,             // line 41
                                    $context['section'], 'type', [], 'any', false, false, false, 41)), 'app'))]]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['section'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 45
        ob_start();
        // line 46
        yield "    var columns = [
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
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
            throw new RuntimeError('Variable "tableData" does not exist.', 58, $this->source);
        })()));
        yield '
    });
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/sections/_index.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/sections/_index.twig');
    }

    // line 22
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_actionButton(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 23
        yield '    <a href="';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('settings/sections/new'), 'html', null, true);
        yield '" class="btn submit add icon">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New section', 'app'), 'html', null, true);
        yield '</a>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
        yield from [];
    }

    // line 27
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 28
        yield '    <div id="sections-vue-admin-table"></div>
';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/sections/_index.twig';
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
        return [132 => 28,  124 => 27,  113 => 23,  105 => 22,  99 => 1,  93 => 58,  79 => 46,  77 => 45,  71 => 41,  70 => 40,  69 => 39,  68 => 38,  67 => 37,  66 => 36,  65 => 35,  63 => 33,  59 => 32,  57 => 31,  55 => 18,  53 => 6,  51 => 4,  49 => 2,  41 => 1];
    }

    public function getSourceContext(): Source
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
", 'settings/sections/_index.twig', '/tmp/packages/craft5/src/templates/settings/sections/_index.twig');
    }
}
