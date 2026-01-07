<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/utilities/SystemReport.twig */
class __TwigTemplate_e80a2a0682e751f3cf97d0fa93dd6abf extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/SystemReport.twig');
        // line 1
        echo '<div class="readable">
    <h2>';
        // line 2
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Application Info', 'app'), 'html', null, true);
        echo '</h2>

    <table class="data fullwidth fixed-layout" dir="ltr">
        <tbody>
            ';
        // line 6
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['appInfo']) || array_key_exists('appInfo', $context) ? $context['appInfo'] : (function () {
            throw new RuntimeError('Variable "appInfo" does not exist.', 6, $this->source);
        })()));
        foreach ($context['_seq'] as $context['label'] => $context['value']) {
            // line 7
            echo '                <tr>
                    <th class="light">';
            // line 8
            echo twig_escape_filter($this->env, $context['label'], 'html', null, true);
            echo '</th>
                    <td>';
            // line 9
            echo twig_escape_filter($this->env, $context['value'], 'html', null, true);
            echo '</td>
                </tr>
            ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['label'], $context['value'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 12
        echo '        </tbody>
    </table>

    <h2>';
        // line 15
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Plugins', 'app'), 'html', null, true);
        echo '</h2>

    ';
        // line 17
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['plugins']) || array_key_exists('plugins', $context) ? $context['plugins'] : (function () {
            throw new RuntimeError('Variable "plugins" does not exist.', 17, $this->source);
        })()))) {
            // line 18
            echo '        <table class="data fullwidth fixed-layout" dir="ltr">
            <tbody>
                ';
            // line 20
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['plugins']) || array_key_exists('plugins', $context) ? $context['plugins'] : (function () {
                throw new RuntimeError('Variable "plugins" does not exist.', 20, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['plugin']) {
                // line 21
                echo '                    <tr>
                        <th class="light">';
                // line 22
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['plugin'], 'name', []), 'html', null, true);
                echo '</th>
                        <td>';
                // line 23
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['plugin'], 'version', []), 'html', null, true);
                echo '</td>
                    </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['plugin'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 26
            echo '            </tbody>
        </table>
    ';
        } else {
            // line 29
            echo '        <p>';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No plugins are enabled.', 'app'), 'html', null, true);
            echo '</p>
    ';
        }
        // line 31
        echo '
    <h2>';
        // line 32
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Modules', 'app'), 'html', null, true);
        echo '</h2>

    ';
        // line 34
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['modules']) || array_key_exists('modules', $context) ? $context['modules'] : (function () {
            throw new RuntimeError('Variable "modules" does not exist.', 34, $this->source);
        })()))) {
            // line 35
            echo '        <table class="data fullwidth fixed-layout" dir="ltr">
            <tbody>
                ';
            // line 37
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['modules']) || array_key_exists('modules', $context) ? $context['modules'] : (function () {
                throw new RuntimeError('Variable "modules" does not exist.', 37, $this->source);
            })()));
            foreach ($context['_seq'] as $context['id'] => $context['class']) {
                // line 38
                echo '                    <tr>
                        <th class="light">';
                // line 39
                echo twig_escape_filter($this->env, $context['id'], 'html', null, true);
                echo '</th>
                        <td>';
                // line 40
                echo twig_escape_filter($this->env, $context['class'], 'html', null, true);
                echo '</td>
                    </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['id'], $context['class'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 43
            echo '            </tbody>
        </table>
    ';
        } else {
            // line 46
            echo '        <p>';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No modules are installed.', 'app'), 'html', null, true);
            echo '</p>
    ';
        }
        // line 48
        echo '
  <h2>';
        // line 49
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Aliases', 'app'), 'html', null, true);
        echo '</h2>
  <p>
      ';
        // line 51
        echo $this->extensions['craft\web\twig\Extension']->translateFilter('The following <a href="{url}">aliases</a> are defined:', 'app', ['url' => 'https://craftcms.com/docs/4.x/config/#aliases']);
        // line 53
        echo '
  </p>

  <table class="data fullwidth fixed-layout" dir="ltr">
      <tbody>
          ';
        // line 58
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['aliases']) || array_key_exists('aliases', $context) ? $context['aliases'] : (function () {
            throw new RuntimeError('Variable "aliases" does not exist.', 58, $this->source);
        })()));
        foreach ($context['_seq'] as $context['alias'] => $context['value']) {
            // line 59
            echo '              <tr>
                  <th class="light">';
            // line 60
            echo twig_escape_filter($this->env, $context['alias'], 'html', null, true);
            echo '</th>
                  <td>';
            // line 61
            echo twig_escape_filter($this->env, $context['value'], 'html', null, true);
            echo '</td>
              </tr>
          ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['alias'], $context['value'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 64
        echo '      </tbody>
  </table>

    <h2>';
        // line 67
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Requirements', 'app'), 'html', null, true);
        echo '</h2>

    <table class="data fullwidth" dir="ltr">
        <tbody>
        ';
        // line 71
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['requirements']) || array_key_exists('requirements', $context) ? $context['requirements'] : (function () {
            throw new RuntimeError('Variable "requirements" does not exist.', 71, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['requirement']) {
            // line 72
            echo '            <tr>
                <td class="thin centeralign">
                    ';
            // line 74
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['requirement'], 'error', [])) {
                // line 75
                echo '                        <span class="error" title="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Failed', 'app'), 'html', null, true);
                echo '" aria-label="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Failed', 'app'), 'html', null, true);
                echo '" data-icon="error"></span>
                    ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 76
                $context['requirement'], 'warning', [])) {
                // line 77
                echo '                        <span class="warning" title="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Passed with warning', 'app'), 'html', null, true);
                echo '" aria-label="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Passed with warning', 'app'), 'html', null, true);
                echo '" data-icon="alert"></span>
                    ';
            } else {
                // line 79
                echo '                        <span class="success" title="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Passed', 'app'), 'html', null, true);
                echo '" aria-label="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Passed', 'app'), 'html', null, true);
                echo '" data-icon="check"></span>
                    ';
            }
            // line 81
            echo '                </td>
                <td>';
            // line 82
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['requirement'], 'name', []), 'html', null, true);
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['requirement'], 'memo', [])) {
                echo ' <span class="info">';
                echo craft\helpers\Template::attribute($this->env, $this->source, $context['requirement'], 'memo', []);
                echo '</span>';
            }
            echo '</td>
            </tr>
        ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['requirement'], $context['_parent'], $context['loop']);
        // line 85
        echo '        </tbody>
    </table>
</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/SystemReport.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/SystemReport.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [261 => 85,  247 => 82,  244 => 81,  236 => 79,  228 => 77,  226 => 76,  219 => 75,  217 => 74,  213 => 72,  209 => 71,  202 => 67,  197 => 64,  188 => 61,  184 => 60,  181 => 59,  177 => 58,  170 => 53,  168 => 51,  163 => 49,  160 => 48,  154 => 46,  149 => 43,  140 => 40,  136 => 39,  133 => 38,  129 => 37,  125 => 35,  123 => 34,  118 => 32,  115 => 31,  109 => 29,  104 => 26,  95 => 23,  91 => 22,  88 => 21,  84 => 20,  80 => 18,  78 => 17,  73 => 15,  68 => 12,  59 => 9,  55 => 8,  52 => 7,  48 => 6,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div class=\"readable\">
    <h2>{{ \"Application Info\"|t('app') }}</h2>

    <table class=\"data fullwidth fixed-layout\" dir=\"ltr\">
        <tbody>
            {% for label, value in appInfo %}
                <tr>
                    <th class=\"light\">{{ label }}</th>
                    <td>{{ value }}</td>
                </tr>
            {% endfor %}
        </tbody>
    </table>

    <h2>{{ \"Plugins\"|t('app') }}</h2>

    {% if plugins|length %}
        <table class=\"data fullwidth fixed-layout\" dir=\"ltr\">
            <tbody>
                {% for plugin in plugins %}
                    <tr>
                        <th class=\"light\">{{ plugin.name }}</th>
                        <td>{{ plugin.version }}</td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    {% else %}
        <p>{{ 'No plugins are enabled.'|t('app') }}</p>
    {% endif %}

    <h2>{{ 'Modules'|t('app') }}</h2>

    {% if modules|length %}
        <table class=\"data fullwidth fixed-layout\" dir=\"ltr\">
            <tbody>
                {% for id, class in modules %}
                    <tr>
                        <th class=\"light\">{{ id }}</th>
                        <td>{{ class }}</td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    {% else %}
        <p>{{ 'No modules are installed.'|t('app') }}</p>
    {% endif %}

  <h2>{{ 'Aliases'|t('app') }}</h2>
  <p>
      {{ 'The following <a href=\"{url}\">aliases</a> are defined:'|t('app', {
          url: 'https://craftcms.com/docs/4.x/config/#aliases',
      })|raw }}
  </p>

  <table class=\"data fullwidth fixed-layout\" dir=\"ltr\">
      <tbody>
          {% for alias, value in aliases %}
              <tr>
                  <th class=\"light\">{{ alias }}</th>
                  <td>{{ value }}</td>
              </tr>
          {% endfor %}
      </tbody>
  </table>

    <h2>{{ \"Requirements\"|t('app') }}</h2>

    <table class=\"data fullwidth\" dir=\"ltr\">
        <tbody>
        {% for requirement in requirements %}
            <tr>
                <td class=\"thin centeralign\">
                    {% if requirement.error %}
                        <span class=\"error\" title=\"{{ 'Failed'|t('app') }}\" aria-label=\"{{ 'Failed'|t('app') }}\" data-icon=\"error\"></span>
                    {% elseif requirement.warning %}
                        <span class=\"warning\" title=\"{{ 'Passed with warning'|t('app') }}\" aria-label=\"{{ 'Passed with warning'|t('app') }}\" data-icon=\"alert\"></span>
                    {% else %}
                        <span class=\"success\" title=\"{{ 'Passed'|t('app') }}\" aria-label=\"{{ 'Passed'|t('app') }}\" data-icon=\"check\"></span>
                    {% endif %}
                </td>
                <td>{{ requirement.name }}{% if requirement.memo %} <span class=\"info\">{{ requirement.memo|raw }}</span>{% endif %}</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
</div>
", '_components/utilities/SystemReport.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/SystemReport.twig');
    }
}
