<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/assets/transforms/_index.twig */
class __TwigTemplate_f6bc32254c1efc4560012f417f8aea1e extends Template
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
        craft\helpers\Template::beginProfile('template', 'settings/assets/transforms/_index.twig');
        // line 2
        $context['selectedNavItem'] = 'transforms';
        // line 4
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 4, $this->source);
        })()), 'registerAssetBundle', ['craft\\web\\assets\\admintable\\AdminTableAsset'], 'method', false, false, false, 4);
        // line 6
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 6, $this->source);
        })()), 'registerTranslations', ['app', ['Name', 'Handle', 'Mode', 'Dimensions', 'Interlace', 'Format', 'No image transforms exist yet.']], 'method', false, false, false, 6);
        // line 24
        $context['tableData'] = [];
        // line 25
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['transforms']) || array_key_exists('transforms', $context) ? $context['transforms'] : (function () {
            throw new RuntimeError('Variable "transforms" does not exist.', 25, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['transform']) {
            // line 26
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'mode', [], 'any', false, false, false, 26)) {
                // line 27
                $context['mode'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['modes']) || array_key_exists('modes', $context) ? $context['modes'] : (function () {
                    throw new RuntimeError('Variable "modes" does not exist.', 27, $this->source);
                })()), craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'mode', [], 'any', false, false, false, 27), [], 'array', false, false, false, 27);
            }
            // line 30
            $context['tableData'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
                throw new RuntimeError('Variable "tableData" does not exist.', 30, $this->source);
            })()), [['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 31
                $context['transform'], 'id', [], 'any', false, false, false, 31), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 32
                    $context['transform'], 'name', [], 'any', false, false, false, 32), 'site'), 'url' => craft\helpers\UrlHelper::url(('settings/assets/transforms/'.craft\helpers\Template::attribute($this->env, $this->source,             // line 33
                        $context['transform'], 'handle', [], 'any', false, false, false, 33))), 'handle' => craft\helpers\Template::attribute($this->env, $this->source,             // line 34
                            $context['transform'], 'handle', [], 'any', false, false, false, 34), 'mode' => ((            // line 35
                                $context['mode']) ?? (null)), 'dimensions' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 36
                                    $context['transform'], 'width', [], 'any', false, false, false, 36) ?: $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Auto', 'app'))).' × ').(craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'height', [], 'any', false, false, false, 36) ?: $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Auto', 'app')))), 'interlace' => ((craft\helpers\Template::attribute($this->env, $this->source,             // line 37
                                        $context['transform'], 'interlace', [], 'any', false, false, false, 37)) ? (Twig\Extension\CoreExtension::capitalize($this->env->getCharset(), craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'interlace', [], 'any', false, false, false, 37))) : ($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('None', 'app')))), 'format' => ((craft\helpers\Template::attribute($this->env, $this->source,             // line 38
                                            $context['transform'], 'format', [], 'any', false, false, false, 38)) ? (Twig\Extension\CoreExtension::capitalize($this->env->getCharset(), craft\helpers\Template::attribute($this->env, $this->source, $context['transform'], 'format', [], 'any', false, false, false, 38))) : ($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Auto', 'app'))))]]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['transform'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 42
        ob_start();
        // line 43
        yield "var columns = [
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
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['tableData']) || array_key_exists('tableData', $context) ? $context['tableData'] : (function () {
            throw new RuntimeError('Variable "tableData" does not exist.', 57, $this->source);
        })()));
        yield ',
    });
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 1
        $this->parent = $this->loadTemplate('settings/assets/_layout', 'settings/assets/transforms/_index.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/assets/transforms/_index.twig');
    }

    // line 16
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 17
        yield '    <div id="transforms-vue-admin-table"></div>

    <div class="buttons">
        <a class="btn submit add icon" href="';
        // line 20
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('settings/assets/transforms/new'), 'html', null, true);
        yield '">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New image transform', 'app'), 'html', null, true);
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
        return 'settings/assets/transforms/_index.twig';
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
        return [122 => 20,  117 => 17,  109 => 16,  103 => 1,  97 => 57,  81 => 43,  79 => 42,  73 => 38,  72 => 37,  71 => 36,  70 => 35,  69 => 34,  68 => 33,  67 => 32,  66 => 31,  65 => 30,  62 => 27,  60 => 26,  56 => 25,  54 => 24,  52 => 6,  50 => 4,  48 => 2,  40 => 1];
    }

    public function getSourceContext(): Source
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
", 'settings/assets/transforms/_index.twig', '/tmp/packages/craft5/src/templates/settings/assets/transforms/_index.twig');
    }
}
