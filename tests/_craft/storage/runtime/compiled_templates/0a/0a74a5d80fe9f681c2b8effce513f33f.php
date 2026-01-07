<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* _layouts/basecp.twig */
class __TwigTemplate_de397f69980097a837324a8e8970dde5 extends Template
{
    private readonly Source $source;

    /**
     * @var array<string, Template>
     */
    private array $macros = [];

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
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return '_layouts/base';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_layouts/basecp.twig');
        // line 4
        if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
        })()), 'app', [], 'any', false, false, false, 4), 'request', [], 'any', false, false, false, 4), 'isMobileBrowser', [true], 'method', false, false, false, 4)) {
            // line 5
            $context['bodyClass'] = $this->extensions['craft\web\twig\Extension']->pushFilter(craft\helpers\Html::explodeClass((($context['bodyClass']) ?? ([]))), 'mobile');
        }
        // line 8
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 8, $this->source);
        })()), 'registerTranslations', ['app', ['Show', 'Hide']], 'method', false, false, false, 8);
        // line 13
        $context['localeData'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 13, $this->source);
        })()), 'app', [], 'any', false, false, false, 13), 'locale', [], 'any', false, false, false, 13);
        // line 14
        $context['orientation'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['localeData']) || array_key_exists('localeData', $context) ? $context['localeData'] : (function () {
            throw new RuntimeError('Variable "localeData" does not exist.', 14, $this->source);
        })()), 'getOrientation', [], 'method', false, false, false, 14);
        // line 1
        $this->parent = $this->loadTemplate('_layouts/base', '_layouts/basecp.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/basecp.twig');
    }

    // line 24
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_foot(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'foot');
        // line 25
        yield '    <form id="x" method="post" accept-charset="UTF-8">
        ';
        // line 26
        yield craft\helpers\Html::csrfInput();
        yield '
    </form>
    <noscript>
        ';
        // line 29
        yield CoreExtension::callMacro($macros['_self'], 'macro_noAccessMessage', [$this->extensions['craft\web\twig\Extension']->translateFilter('JavaScript must be enabled to access the Craft CMS control panel.', 'app')], 29, $context, $this->getSourceContext());
        yield "
    </noscript>
    <script type=\"text/javascript\">
        if (!('noModule' in HTMLScriptElement.prototype)) {
            document.write(\"";
        // line 33
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(CoreExtension::callMacro($macros['_self'], 'macro_noAccessMessage', [$this->extensions['craft\web\twig\Extension']->translateFilter('The Craft CMS control panel requires a newer web browser.', 'app')], 33, $context, $this->getSourceContext()), 'js'), 'html', null, true);
        yield '");
        }
    </script>

    ';
        // line 37
        ob_start();
        // line 38
        yield "        // Picture element HTML5 shiv
        document.createElement('picture');
    ";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 1]);
        craft\helpers\Template::endProfile('block', 'foot');
        yield from [];
    }

    // line 16
    public function macro_noAccessMessage($__message__ = null, ...$__varargs__)
    {
        $context = [
            'message' => $__message__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'noAccessMessage');
            // line 17
            yield '    <div class="message-container no-access">
        <div class="pane">
            <p>';
            // line 19
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['message']) || array_key_exists('message', $context) ? $context['message'] : (function () {
                throw new RuntimeError('Variable "message" does not exist.', 19, $this->source);
            })()), 'html', null, true);
            yield '</p>
        </div>
    </div>
';
            craft\helpers\Template::endProfile('macro', 'noAccessMessage');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/basecp.twig';
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
        return [125 => 19,  121 => 17,  108 => 16,  99 => 38,  97 => 37,  90 => 33,  83 => 29,  77 => 26,  74 => 25,  66 => 24,  60 => 1,  58 => 14,  56 => 13,  54 => 8,  51 => 5,  49 => 4,  41 => 1];
    }

    public function getSourceContext(): Source
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
", '_layouts/basecp.twig', '/tmp/packages/craft5/src/templates/_layouts/basecp.twig');
    }
}
