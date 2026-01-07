<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* settings/routes */
class __TwigTemplate_8881953c53d63a07a02c01afd4a9f40a extends Template
{
    private $source;

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
    protected function doGetParent(array $context)
    {
        // line 3
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'settings/routes');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Routes', 'app');
        // line 10
        $context['crumbs'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 14
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 14, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\routes\\RoutesAsset'], 'method');
        // line 16
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 16, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Add a token', 1 => 'Are you sure you want to delete this route?', 2 => 'Couldn’t save new route order.', 3 => 'Couldn’t save route.', 4 => 'Create a new route', 5 => 'Edit Route', 6 => 'Global', 7 => 'If the URI looks like this', 8 => 'Load this template', 9 => 'New route order saved.', 10 => 'Route deleted.', 11 => 'Route Saved.', 12 => 'The URI can’t begin with the {setting} config setting.']], 'method');
        // line 33
        $context['routes'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 33, $this->source);
        })()), 'routes', []), 'getProjectConfigRoutes', [], 'method');
        // line 59
        ob_start();
        // line 60
        echo '    Craft.routes.tokens = {
        ';
        // line 61
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['tokens']) || array_key_exists('tokens', $context) ? $context['tokens'] : (function () {
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
            echo '            ';
            if (! craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'first', [])) {
                echo ',';
            }
            // line 63
            echo '            "';
            echo twig_escape_filter($this->env, twig_escape_filter($this->env, $context['name'], 'js'), 'html', null, true);
            echo '": "';
            echo twig_escape_filter($this->env, twig_escape_filter($this->env, $context['pattern'], 'js'), 'html', null, true);
            echo '"
        ';
            $context['loop']['index0']++;
            $context['loop']['index']++;
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                $context['loop']['revindex0']--;
                $context['loop']['revindex']--;
                $context['loop']['last'] = $context['loop']['revindex0'] === 0;
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['name'], $context['pattern'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 65
        echo '    };
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/routes', 3);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/routes');
    }

    // line 6
    public function block_actionButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 7
        echo '    <button type="button" id="add-route-btn" class="btn submit add icon">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New route', 'app'), 'html', null, true);
        echo '</button>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
    }

    // line 36
    public function block_main($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'main');
        // line 37
        echo '    <p id="noroutes"';
        if ((isset($context['routes']) || array_key_exists('routes', $context) ? $context['routes'] : (function () {
            throw new RuntimeError('Variable "routes" does not exist.', 37, $this->source);
        })())) {
            echo ' class="hidden"';
        }
        echo '>
        ';
        // line 38
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No routes exist yet.', 'app'), 'html', null, true);
        echo '
    </p>

    <div id="routes">
        ';
        // line 42
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['routes']) || array_key_exists('routes', $context) ? $context['routes'] : (function () {
            throw new RuntimeError('Variable "routes" does not exist.', 42, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['route']) {
            // line 43
            echo '            <div class="route" data-uid="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'uid', []), 'html', null, true);
            echo '"';
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'siteUid', [])) {
                echo ' data-site-uid="';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'siteUid', []), 'html', null, true);
                echo '"';
            }
            echo '>
                <div class="uri-container">';
            // line 45
            ob_start();
            // line 46
            echo '                        ';
            if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 46, $this->source);
            })()), 'app', []), 'getIsMultiSite', [], 'method')) {
                // line 47
                echo '                            <span class="site">';
                echo twig_escape_filter($this->env, ((craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'siteUid', [])) ? ($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 47, $this->source);
                })()), 'app', []), 'sites', []), 'getSiteByUid', [0 => craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'siteUid', [])], 'method'), 'name', []), 'site')) : ($this->extensions['craft\web\twig\Extension']->translateFilter('Global', 'app'))), 'html', null, true);
                echo '</span>
                        ';
            }
            // line 49
            echo '                        <span class="uri">';
            echo craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'uriDisplayHtml', []);
            echo '</span>
                    ';
            $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 45
            echo twig_spaceless($___internal_parse_0_);
            // line 51
            echo '</div>
                <div class="template">';
            // line 52
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['route'], 'template', []), 'html', null, true);
            echo '</div>
            </div>
        ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['route'], $context['_parent'], $context['loop']);
        // line 55
        echo '    </div>
';
        craft\helpers\Template::endProfile('block', 'main');
    }

    public function getTemplateName()
    {
        return 'settings/routes';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [190 => 55,  181 => 52,  178 => 51,  176 => 45,  170 => 49,  164 => 47,  161 => 46,  159 => 45,  148 => 43,  144 => 42,  137 => 38,  130 => 37,  125 => 36,  117 => 7,  112 => 6,  106 => 3,  102 => 65,  83 => 63,  78 => 62,  61 => 61,  58 => 60,  56 => 59,  54 => 33,  52 => 16,  50 => 14,  48 => 10,  46 => 4,  44 => 1,  36 => 3];
    }

    public function getSourceContext()
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
", 'settings/routes', '/Users/brianhanson/Development/craft5/src/templates/settings/routes.twig');
    }
}
