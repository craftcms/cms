<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/routes */
class __TwigTemplate_a31f04432fb45851061a9793a9cb805c extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'actionButton' => $this->block_actionButton(...),
            'main' => $this->block_main(...),
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
        craft\helpers\Template::beginProfile('template', 'settings/routes');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Routes', 'app');
        // line 10
        $context['crumbs'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 14
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 14, $this->source);
        })()), 'registerAssetBundle', ['craft\\web\\assets\\routes\\RoutesAsset'], 'method', false, false, false, 14);
        // line 16
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 16, $this->source);
        })()), 'registerTranslations', ['app', ['Add a token', 'Are you sure you want to delete this route?', 'Couldn’t save new route order.', 'Couldn’t save route.', 'Create a new route', 'Edit Route', 'Global', 'If the URI looks like this', 'Load this template', 'New route order saved.', 'Route deleted.', 'Route Saved.', 'The URI can’t begin with the {setting} config setting.']], 'method', false, false, false, 16);
        // line 33
        $context['routes'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 33, $this->source);
        })()), 'routes', [], 'any', false, false, false, 33), 'getProjectConfigRoutes', [], 'method', false, false, false, 33);
        // line 59
        ob_start();
        // line 60
        yield '    Craft.routes.tokens = {
        ';
        // line 61
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['tokens']) || array_key_exists('tokens', $context) ? $context['tokens'] : (function () {
            throw new RuntimeError('Variable "tokens" does not exist.', 61, $this->source);
        })()));
        $context['loop'] = [
            'parent' => $context['_parent'],
            'index0' => 0,
            'index' => 1,
            'first' => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = $length === 1;
        }
        foreach ($context['_seq'] as $context['name'] => $context['pattern']) {
            // line 62
            yield '            ';
            if (! craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'first', [], 'any', false, false, false, 62)) {
                yield ',';
            }
            // line 63
            yield '            "';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['name'], 'js'), 'html', null, true);
            yield '": "';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['pattern'], 'js'), 'html', null, true);
            yield '"
        ';
            $context['loop']['index0']++;
            $context['loop']['index']++;
            $context['loop']['first'] = false;
            if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                $context['loop']['revindex0']--;
                $context['loop']['revindex']--;
                $context['loop']['last'] = $context['loop']['revindex0'] === 0;
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['name'], $context['pattern'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 65
        yield '    };
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/routes', 3);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/routes');
    }

    // line 6
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_actionButton(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 7
        yield '    <button type="button" id="add-route-btn" class="btn submit add icon">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New route', 'app'), 'html', null, true);
        yield '</button>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
        yield from [];
    }

    // line 36
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_main(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'main');
        // line 37
        yield '    <p id="noroutes"';
        if ((isset($context['routes']) || array_key_exists('routes', $context) ? $context['routes'] : (function () {
            throw new RuntimeError('Variable "routes" does not exist.', 37, $this->source);
        })())) {
            yield ' class="hidden"';
        }
        yield '>
        ';
        // line 38
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('No routes exist yet.', 'app'), 'html', null, true);
        yield '
    </p>

    <div id="routes">
        ';
        // line 42
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['routes']) || array_key_exists('routes', $context) ? $context['routes'] : (function () {
            throw new RuntimeError('Variable "routes" does not exist.', 42, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['route']) {
            // line 43
            yield '            <div class="route" data-uid="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'uid', [], 'any', false, false, false, 43), 'html', null, true);
            yield '"';
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'siteUid', [], 'any', false, false, false, 43)) {
                yield ' data-site-uid="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'siteUid', [], 'any', false, false, false, 43), 'html', null, true);
                yield '"';
            }
            yield '>
                <div class="uri-container">';
            // line 45
            $___internal_parse_0_ = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
                // line 46
                yield '                        ';
                if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 46, $this->source);
                })()), 'app', [], 'any', false, false, false, 46), 'getIsMultiSite', [], 'method', false, false, false, 46)) {
                    // line 47
                    yield '                            <span class="site">';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(((craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'siteUid', [], 'any', false, false, false, 47)) ? ($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                        throw new RuntimeError('Variable "craft" does not exist.', 47, $this->source);
                    })()), 'app', [], 'any', false, false, false, 47), 'sites', [], 'any', false, false, false, 47), 'getSiteByUid', [craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'siteUid', [], 'any', false, false, false, 47)], 'method', false, false, false, 47), 'name', [], 'any', false, false, false, 47), 'site')) : ($this->extensions['craft\web\twig\Extension']->translateFilter('Global', 'app'))), 'html', null, true);
                    yield '</span>
                        ';
                }
                // line 49
                yield '                        <span class="uri">';
                yield craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'uriDisplayHtml', [], 'any', false, false, false, 49);
                yield '</span>
                    ';
                yield from [];
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 45
            yield Twig\Extension\CoreExtension::spaceless($___internal_parse_0_);
            // line 51
            yield '</div>
                <div class="template">';
            // line 52
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'template', [], 'any', false, false, false, 52), 'html', null, true);
            yield '</div>
            </div>
        ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['route'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 55
        yield '    </div>
';
        craft\helpers\Template::endProfile('block', 'main');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/routes';
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
        return [203 => 55,  194 => 52,  191 => 51,  189 => 45,  182 => 49,  176 => 47,  173 => 46,  171 => 45,  160 => 43,  156 => 42,  149 => 38,  142 => 37,  134 => 36,  125 => 7,  117 => 6,  111 => 3,  107 => 65,  88 => 63,  83 => 62,  66 => 61,  63 => 60,  61 => 59,  59 => 33,  57 => 16,  55 => 14,  53 => 10,  51 => 4,  49 => 1,  41 => 3];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% requireAdmin %}

{% extends \"_layouts/cp\" %}
{% set title = \"Routes\"|t('app') %}

{% block actionButton %}
    <button type=\"button\" id=\"add-route-btn\" class=\"btn submit add icon\">{{ \"New route\"|t('app') }}</button>
{% endblock %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% do view.registerAssetBundle(\"craft\\\\web\\\\assets\\\\routes\\\\RoutesAsset\") %}

{% do view.registerTranslations('app', [
    \"Add a token\",
    \"Are you sure you want to delete this route?\",
    \"Couldn’t save new route order.\",
    \"Couldn’t save route.\",
    \"Create a new route\",
    \"Edit Route\",
    \"Global\",
    \"If the URI looks like this\",
    \"Load this template\",
    \"New route order saved.\",
    \"Route deleted.\",
    \"Route Saved.\",
    \"The URI can’t begin with the {setting} config setting.\",
]) %}


{% set routes = craft.routes.getProjectConfigRoutes() %}


{% block main %}
    <p id=\"noroutes\"{% if routes %} class=\"hidden\"{% endif %}>
        {{ \"No routes exist yet.\"|t('app') }}
    </p>

    <div id=\"routes\">
        {% for route in routes %}
            <div class=\"route\" data-uid=\"{{ route.uid }}\"{% if route.siteUid %} data-site-uid=\"{{ route.siteUid }}\"{% endif %}>
                <div class=\"uri-container\">
                    {%- apply spaceless %}
                        {% if craft.app.getIsMultiSite() %}
                            <span class=\"site\">{{ route.siteUid ? craft.app.sites.getSiteByUid(route.siteUid).name|t('site') : \"Global\"|t('app') }}</span>
                        {% endif %}
                        <span class=\"uri\">{{ route.uriDisplayHtml|raw }}</span>
                    {% endapply -%}
                </div>
                <div class=\"template\">{{ route.template }}</div>
            </div>
        {% endfor %}
    </div>
{% endblock %}


{% js %}
    Craft.routes.tokens = {
        {% for name, pattern in tokens %}
            {% if not loop.first %},{% endif %}
            \"{{ name|e('js') }}\": \"{{ pattern|e('js') }}\"
        {% endfor %}
    };
{% endjs %}
", 'settings/routes', '/tmp/packages/craft5/src/templates/settings/routes.twig');
    }
}
