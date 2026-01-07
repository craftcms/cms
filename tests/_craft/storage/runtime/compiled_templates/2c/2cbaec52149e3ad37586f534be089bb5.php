<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/assets/transforms/_index.twig */
class __TwigTemplate_537ad46e762933f63ea88ef9b07d8e52 extends Template
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
        craft\helpers\Template::beginProfile('template', 'settings/assets/transforms/_index.twig');
        // line 2
        $context['selectedNavItem'] = 'transforms';
        // line 4
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 4, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\admintable\\AdminTableAsset'], 'method');
        // line 6
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 6, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Name', 1 => 'Handle', 2 => 'Mode', 3 => 'Dimensions', 4 => 'Interlace', 5 => 'Format', 6 => 'No image transforms exist yet.']], 'method');
        // line 24
        $context['tableData'] = [];
        // line 25
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['transforms']) || array_key_exists('transforms', $context) ? $context['transforms'] : (function () {
            throw new RuntimeError('Variable "transforms" does not exist.', 25, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['transform']) {
            // line 26
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'mode', [])) {
                // line 27
                $context['mode'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['modes']) || array_key_exists('modes', $context) ? $context['modes'] : (function () {
                    throw new RuntimeError('Variable "modes" does not exist.', 27, $this->source);
                })()), craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'mode', []), [], 'array');
            }
            // line 30
            $context['tableData'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
                throw new RuntimeError('Variable "tableData" does not exist.', 30, $this->source);
            })()), [0 => ['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 31
                $context['transform'], 'id', []), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 32
                    $context['transform'], 'name', []), 'site'), 'url' => craft\helpers\UrlHelper::url(('settings/assets/transforms/'.craft\helpers\Template::attribute($this->env, $this->source,             // line 33
                        $context['transform'], 'handle', []))), 'handle' => craft\helpers\Template::attribute($this->env, $this->source,             // line 34
                            $context['transform'], 'handle', []), 'mode' => ((            // line 35
                                $context['mode']) ?? (null)), 'dimensions' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 36
                                    $context['transform'], 'width', []) ?: twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Auto', 'app'))).' × ').(craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'height', []) ?: twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Auto', 'app')))), 'interlace' => ((craft\helpers\Template::attribute($this->env, $this->source,             // line 37
                                        $context['transform'], 'interlace', [])) ? (twig_capitalize_string_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'interlace', []))) : (twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('None', 'app')))), 'format' => ((craft\helpers\Template::attribute($this->env, $this->source,             // line 38
                                            $context['transform'], 'format', [])) ? (twig_capitalize_string_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'format', []))) : (twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Auto', 'app'))))]]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['transform'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 42
        ob_start();
        // line 43
        echo "var columns = [
    { name: '__slot:title', title: Craft.t('app', 'Name') },
    { name: '__slot:handle', title: Craft.t('app', 'Handle') },
    { name: 'mode', title: Craft.t('app', 'Mode'), },
    { name: 'dimensions', title: Craft.t('app', 'Dimensions'), },
    { name: 'interlace', title: Craft.t('app', 'Interlace'), },
    { name: 'format', title: Craft.t('app', 'Format'), }
];

new Craft.VueAdminTable({
    columns: columns,
    container: '#transforms-vue-admin-table',
    deleteAction: 'image-transforms/delete',
    emptyMessage: Craft.t('app', 'No image transforms exist yet.'),
    tableData: ";
        // line 57
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
            throw new RuntimeError('Variable "tableData" does not exist.', 57, $this->source);
        })()));
        echo ',
    });
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 1
        $this->parent = $this->loadTemplate('settings/assets/_layout', 'settings/assets/transforms/_index.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/assets/transforms/_index.twig');
    }

    // line 16
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 17
        echo '    <div id="transforms-vue-admin-table"></div>

    <div class="buttons">
        <a class="btn submit add icon" href="';
        // line 20
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('settings/assets/transforms/new'), 'html', null, true);
        echo '">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New image transform', 'app'), 'html', null, true);
        echo '</a>
    </div>
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/assets/transforms/_index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [114 => 20,  109 => 17,  104 => 16,  98 => 1,  92 => 57,  76 => 43,  74 => 42,  68 => 38,  67 => 37,  66 => 36,  65 => 35,  64 => 34,  63 => 33,  62 => 32,  61 => 31,  60 => 30,  57 => 27,  55 => 26,  51 => 25,  49 => 24,  47 => 6,  45 => 4,  43 => 2,  35 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"settings/assets/_layout\" %}
{% set selectedNavItem = 'transforms' %}

{% do view.registerAssetBundle('craft\\\\web\\\\assets\\\\admintable\\\\AdminTableAsset') -%}

{% do view.registerTranslations('app', [
    \"Name\",
    \"Handle\",
    \"Mode\",
    \"Dimensions\",
    \"Interlace\",
    \"Format\",
    \"No image transforms exist yet.\",
]) %}

{% block content %}
    <div id=\"transforms-vue-admin-table\"></div>

    <div class=\"buttons\">
        <a class=\"btn submit add icon\" href=\"{{ url('settings/assets/transforms/new') }}\">{{ \"New image transform\"|t('app') }}</a>
    </div>
{% endblock %}

{% set tableData = [] %}
{% for transform in transforms %}
    {% if transform.mode %}
        {% set mode = modes[transform.mode] %}
    {%  endif %}

    {% set tableData = tableData|merge([{
        id: transform.id,
        title: transform.name|t('site'),
        url: url('settings/assets/transforms/' ~ transform.handle),
        handle: transform.handle,
        mode: mode ?? null,
        dimensions: (transform.width ? transform.width : 'Auto'|t('app')|e) ~ \" × \" ~ (transform.height ? transform.height : 'Auto'|t('app')|e),
        interlace: transform.interlace ? transform.interlace|capitalize : 'None'|t('app')|e,
        format: transform.format ? transform.format|capitalize : 'Auto'|t('app')|e,
    }]) %}
{% endfor %}

{% js %}
var columns = [
    { name: '__slot:title', title: Craft.t('app', 'Name') },
    { name: '__slot:handle', title: Craft.t('app', 'Handle') },
    { name: 'mode', title: Craft.t('app', 'Mode'), },
    { name: 'dimensions', title: Craft.t('app', 'Dimensions'), },
    { name: 'interlace', title: Craft.t('app', 'Interlace'), },
    { name: 'format', title: Craft.t('app', 'Format'), }
];

new Craft.VueAdminTable({
    columns: columns,
    container: '#transforms-vue-admin-table',
    deleteAction: 'image-transforms/delete',
    emptyMessage: Craft.t('app', 'No image transforms exist yet.'),
    tableData: {{ tableData|json_encode|raw }},
    });
{% endjs %}
", 'settings/assets/transforms/_index.twig', '/Users/brianhanson/Development/craft5/src/templates/settings/assets/transforms/_index.twig');
    }
}
