<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/utilities/DeprecationErrors/index.twig */
class __TwigTemplate_8084d26b56c0caee530373583e2b709e extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_components/utilities/DeprecationErrors/index.twig');
        // line 1
        if ((isset($context['logs']) || array_key_exists('logs', $context) ? $context['logs'] : (function () {
            throw new RuntimeError('Variable "logs" does not exist.', 1, $this->source);
        })())) {
            // line 2
            echo '    <div class="buttons first">
        <button type="button" id="clearall" class="btn submit">';
            // line 3
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Clear all', 'app'), 'html', null, true);
            echo '</button>
    </div>
';
        }
        // line 6
        echo '

<div class="readable">
    <p id="nologs" class="zilch';
        // line 9
        if ((isset($context['logs']) || array_key_exists('logs', $context) ? $context['logs'] : (function () {
            throw new RuntimeError('Variable "logs" does not exist.', 9, $this->source);
        })())) {
            echo ' hidden';
        }
        echo '">
        ';
        // line 10
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No deprecation warnings to report!', 'app'), 'html', null, true);
        echo '
    </p>

    ';
        // line 13
        if ((isset($context['logs']) || array_key_exists('logs', $context) ? $context['logs'] : (function () {
            throw new RuntimeError('Variable "logs" does not exist.', 13, $this->source);
        })())) {
            // line 14
            echo '        <table id="deprecationerrors" class="data fullwidth fixed-layout">
            <thead>
                <tr>
                    <th>';
            // line 17
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Message', 'app'), 'html', null, true);
            echo '</th>
                    <th>';
            // line 18
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Origin', 'app'), 'html', null, true);
            echo '</th>
                    <th class="nowrap">';
            // line 19
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Last Occurrence', 'app'), 'html', null, true);
            echo '</th>
                    <th class="nowrap">';
            // line 20
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Stack Trace', 'app'), 'html', null, true);
            echo '</th>
                    <th style="width: 14px;"></th>
                </tr>
            </thead>
            <tbody>
            ';
            // line 25
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['logs']) || array_key_exists('logs', $context) ? $context['logs'] : (function () {
                throw new RuntimeError('Variable "logs" does not exist.', 25, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['log']) {
                // line 26
                echo '                <tr data-id="';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'id', []), 'html', null, true);
                echo '">
                    <td>';
                // line 27
                echo $this->extensions['craft\web\twig\Extension']->markdownFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'message', []), null, true, true);
                echo '</td>
                    <td class="code">';
                // line 29
                echo $this->extensions['craft\web\twig\Extension']->replaceFilter(twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'file', [])), '/', '/<wbr>');
                // line 30
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'line', [])) {
                    // line 31
                    echo ':';
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'line', []), 'html', null, true);
                }
                // line 33
                echo '</td>
                    <td>';
                // line 34
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->timestampFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['log'], 'lastOccurrence', [])), 'html', null, true);
                echo '</td>
                    <td class="nowrap viewtraces"><a role="button">';
                // line 35
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Stack Trace', 'app'), 'html', null, true);
                echo '</a></td>
                    <td><a class="delete icon" title="';
                // line 36
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'html', null, true);
                echo '" aria-label="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'html', null, true);
                echo '" role="button"></a></td>
                </tr>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['log'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 39
            echo '            </tbody>
        </table>
    ';
        }
        // line 42
        echo '</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/DeprecationErrors/index.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/DeprecationErrors/index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [141 => 42,  136 => 39,  125 => 36,  121 => 35,  117 => 34,  114 => 33,  110 => 31,  108 => 30,  106 => 29,  102 => 27,  97 => 26,  93 => 25,  85 => 20,  81 => 19,  77 => 18,  73 => 17,  68 => 14,  66 => 13,  60 => 10,  54 => 9,  49 => 6,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
                    <td class=\"nowrap viewtraces\"><a role=\"button\">{{ \"Stack Trace\"|t('app') }}</a></td>
                    <td><a class=\"delete icon\" title=\"{{ 'Delete'|t('app') }}\" aria-label=\"{{ 'Delete'|t('app') }}\" role=\"button\"></a></td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    {% endif %}
</div>
", '_components/utilities/DeprecationErrors/index.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/DeprecationErrors/index.twig');
    }
}
