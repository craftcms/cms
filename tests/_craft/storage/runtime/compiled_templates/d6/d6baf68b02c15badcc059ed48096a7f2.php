<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/utilities/DeprecationErrors/index.twig */
class __TwigTemplate_ab498e34cfb1470be0b05186cfd114eb extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_components/utilities/DeprecationErrors/index.twig');
        // line 1
        if ((isset($context['logs']) || array_key_exists('logs', $context) ? $context['logs'] : (function () {
            throw new RuntimeError('Variable "logs" does not exist.', 1, $this->source);
        })())) {
            // line 2
            yield '    <div class="buttons first">
        <button type="button" id="clearall" class="btn submit">';
            // line 3
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Clear all', 'app'), 'html', null, true);
            yield '</button>
    </div>
';
        }
        // line 6
        yield '

<div class="readable">
    <p id="nologs" class="zilch';
        // line 9
        if ((isset($context['logs']) || array_key_exists('logs', $context) ? $context['logs'] : (function () {
            throw new RuntimeError('Variable "logs" does not exist.', 9, $this->source);
        })())) {
            yield ' hidden';
        }
        yield '">
        ';
        // line 10
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('No deprecation warnings to report!', 'app'), 'html', null, true);
        yield '
    </p>

    ';
        // line 13
        if ((isset($context['logs']) || array_key_exists('logs', $context) ? $context['logs'] : (function () {
            throw new RuntimeError('Variable "logs" does not exist.', 13, $this->source);
        })())) {
            // line 14
            yield '        <table id="deprecationerrors" class="data fullwidth fixed-layout">
            <thead>
                <tr>
                    <th>';
            // line 17
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Message', 'app'), 'html', null, true);
            yield '</th>
                    <th>';
            // line 18
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Origin', 'app'), 'html', null, true);
            yield '</th>
                    <th class="nowrap">';
            // line 19
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Last Occurrence', 'app'), 'html', null, true);
            yield '</th>
                    <th class="nowrap">';
            // line 20
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Stack Trace', 'app'), 'html', null, true);
            yield '</th>
                    <th style="width: 14px;"></th>
                </tr>
            </thead>
            <tbody>
            ';
            // line 25
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['logs']) || array_key_exists('logs', $context) ? $context['logs'] : (function () {
                throw new RuntimeError('Variable "logs" does not exist.', 25, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['log']) {
                // line 26
                yield '                <tr data-id="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'id', [], 'any', false, false, false, 26), 'html', null, true);
                yield '">
                    <td>';
                // line 27
                yield $this->extensions['craft\web\twig\Extension']->markdownFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'message', [], 'any', false, false, false, 27), null, true, true);
                yield '</td>
                    <td class="code">';
                // line 29
                yield $this->extensions['craft\web\twig\Extension']->replaceFilter($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'file', [], 'any', false, false, false, 29)), '/', '/<wbr>');
                // line 30
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'line', [], 'any', false, false, false, 30)) {
                    // line 31
                    yield ':';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'line', [], 'any', false, false, false, 31), 'html', null, true);
                }
                // line 33
                yield '</td>
                    <td>';
                // line 34
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->timestampFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'lastOccurrence', [], 'any', false, false, false, 34)), 'html', null, true);
                yield '</td>
                    <td class="nowrap viewtraces"><a class="btn hairline" role="button">';
                // line 35
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Stack Trace', 'app'), 'html', null, true);
                yield '</a></td>
                    <td><a class="delete icon" title="';
                // line 36
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'html', null, true);
                yield '" aria-label="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'html', null, true);
                yield '" role="button"></a></td>
                </tr>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['log'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 39
            yield '            </tbody>
        </table>
    ';
        }
        // line 42
        yield '</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/DeprecationErrors/index.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/DeprecationErrors/index.twig';
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
        return [146 => 42,  141 => 39,  130 => 36,  126 => 35,  122 => 34,  119 => 33,  115 => 31,  113 => 30,  111 => 29,  107 => 27,  102 => 26,  98 => 25,  90 => 20,  86 => 19,  82 => 18,  78 => 17,  73 => 14,  71 => 13,  65 => 10,  59 => 9,  54 => 6,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% if logs %}
    <div class=\"buttons first\">
        <button type=\"button\" id=\"clearall\" class=\"btn submit\">{{ \"Clear all\"|t('app') }}</button>
    </div>
{% endif %}


<div class=\"readable\">
    <p id=\"nologs\" class=\"zilch{% if logs %} hidden{% endif %}\">
        {{ \"No deprecation warnings to report!\"|t('app') }}
    </p>

    {% if logs %}
        <table id=\"deprecationerrors\" class=\"data fullwidth fixed-layout\">
            <thead>
                <tr>
                    <th>{{ \"Message\"|t('app') }}</th>
                    <th>{{ \"Origin\"|t('app') }}</th>
                    <th class=\"nowrap\">{{ \"Last Occurrence\"|t('app') }}</th>
                    <th class=\"nowrap\">{{ \"Stack Trace\"|t('app') }}</th>
                    <th style=\"width: 14px;\"></th>
                </tr>
            </thead>
            <tbody>
            {% for log in logs %}
                <tr data-id=\"{{ log.id }}\">
                    <td>{{ log.message|md(inlineOnly=true, encode=true)|raw }}</td>
                    <td class=\"code\">
                        {{- log.file|e|replace('/', '/<wbr>')|raw }}
                        {%- if log.line -%}
                            :{{ log.line }}
                        {%- endif -%}
                    </td>
                    <td>{{ log.lastOccurrence|timestamp }}</td>
                    <td class=\"nowrap viewtraces\"><a class=\"btn hairline\" role=\"button\">{{ \"Stack Trace\"|t('app') }}</a></td>
                    <td><a class=\"delete icon\" title=\"{{ 'Delete'|t('app') }}\" aria-label=\"{{ 'Delete'|t('app') }}\" role=\"button\"></a></td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    {% endif %}
</div>
", '_components/utilities/DeprecationErrors/index.twig', '/tmp/packages/craft5/src/templates/_components/utilities/DeprecationErrors/index.twig');
    }
}
