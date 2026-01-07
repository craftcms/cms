<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _layouts/basecp.twig */
class __TwigTemplate_4ebc48fea2dacd99de93f4f43d8d511a extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'foot' => $this->block_foot(...),
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 1
        return '_layouts/base';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_layouts/basecp.twig');
        // line 4
        if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
        })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method')) {
            // line 5
            $context['bodyClass'] = $this->extensions['craft\web\twig\Extension']->pushFilter(craft\helpers\Html::explodeClass((($context['bodyClass']) ?? ([]))), 'mobile');
        }
        // line 8
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 8, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Show', 1 => 'Hide']], 'method');
        // line 13
        $context['localeData'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 13, $this->source);
        })()), 'app', []), 'locale', []);
        // line 14
        $context['orientation'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['localeData']) || array_key_exists('localeData', $context) ? $context['localeData'] : (function () {
            throw new RuntimeError('Variable "localeData" does not exist.', 14, $this->source);
        })()), 'getOrientation', [], 'method');
        // line 1
        $this->parent = $this->loadTemplate('_layouts/base', '_layouts/basecp.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/basecp.twig');
    }

    // line 24
    public function block_foot($context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'foot');
        // line 25
        echo '    <form id="x" method="post" accept-charset="UTF-8">
        ';
        // line 26
        echo craft\helpers\Html::csrfInput();
        echo '
    </form>
    <noscript>
        ';
        // line 29
        echo twig_call_macro($macros['_self'], 'macro_noAccessMessage', [$this->extensions['craft\web\twig\Extension']->translateFilter('JavaScript must be enabled to access the Craft CMS control panel.', 'app')], 29, $context, $this->getSourceContext());
        echo "
    </noscript>
    <script type=\"text/javascript\">
        if (!('noModule' in HTMLScriptElement.prototype)) {
            document.write(\"";
        // line 33
        echo twig_escape_filter($this->env, twig_escape_filter($this->env, twig_call_macro($macros['_self'], 'macro_noAccessMessage', [$this->extensions['craft\web\twig\Extension']->translateFilter('The Craft CMS control panel requires a newer web browser.', 'app')], 33, $context, $this->getSourceContext()), 'js'), 'html', null, true);
        echo '");
        }
    </script>

    ';
        // line 37
        ob_start();
        // line 38
        echo "        // Picture element HTML5 shiv
        document.createElement('picture');
    ";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 1]);
        craft\helpers\Template::endProfile('block', 'foot');
    }

    // line 16
    public function macro_noAccessMessage($__message__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'message' => $__message__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'noAccessMessage');
            // line 17
            echo '    <div class="message-container no-access">
        <div class="pane">
            <p>';
            // line 19
            echo twig_escape_filter($this->env, (isset($context['message']) || array_key_exists('message', $context) ? $context['message'] : (function () {
                throw new RuntimeError('Variable "message" does not exist.', 19, $this->source);
            })()), 'html', null, true);
            echo '</p>
        </div>
    </div>
';
            craft\helpers\Template::endProfile('macro', 'noAccessMessage');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_layouts/basecp.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [117 => 19,  113 => 17,  99 => 16,  91 => 38,  89 => 37,  82 => 33,  75 => 29,  69 => 26,  66 => 25,  61 => 24,  55 => 1,  53 => 14,  51 => 13,  49 => 8,  46 => 5,  44 => 4,  36 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"_layouts/base\" %}

{# Give the body a .mobile class for mobile devices #}
{% if craft.app.request.isMobileBrowser(true) %}
    {% set bodyClass = (bodyClass ?? [])|explodeClass|push('mobile') -%}
{% endif %}

{% do view.registerTranslations('app', [
    \"Show\",
    \"Hide\",
]) %}

{% set localeData = craft.app.locale %}
{% set orientation = localeData.getOrientation() %}

{% macro noAccessMessage(message) %}
    <div class=\"message-container no-access\">
        <div class=\"pane\">
            <p>{{ message }}</p>
        </div>
    </div>
{% endmacro %}

{% block foot %}
    <form id=\"x\" method=\"post\" accept-charset=\"UTF-8\">
        {{ csrfInput() }}
    </form>
    <noscript>
        {{ _self.noAccessMessage('JavaScript must be enabled to access the Craft CMS control panel.'|t('app')) }}
    </noscript>
    <script type=\"text/javascript\">
        if (!('noModule' in HTMLScriptElement.prototype)) {
            document.write(\"{{ _self.noAccessMessage('The Craft CMS control panel requires a newer web browser.'|t('app'))|e('js') }}\");
        }
    </script>

    {% js at head %}
        // Picture element HTML5 shiv
        document.createElement('picture');
    {% endjs %}
{% endblock %}
", '_layouts/basecp.twig', '/Users/brianhanson/Development/craft5/src/templates/_layouts/basecp.twig');
    }
}
