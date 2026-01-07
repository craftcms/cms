<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/utilities/SystemReport.twig */
class __TwigTemplate_9ac59b884f09fc8830aa4880aac15c86 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/SystemReport.twig');
        // line 1
        yield '<div class="readable">
    <h2>';
        // line 2
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Application Info', 'app'), 'html', null, true);
        yield '</h2>

    <table class="data fullwidth fixed-layout" dir="ltr">
        <tbody>
            ';
        // line 6
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['appInfo']) || array_key_exists('appInfo', $context) ? $context['appInfo'] : (function () {
            throw new RuntimeError('Variable "appInfo" does not exist.', 6, $this->source);
        })()));
        foreach ($context['_seq'] as $context['label'] => $context['value']) {
            // line 7
            yield '                <tr>
                    <th class="light">';
            // line 8
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['label'], 'html', null, true);
            yield '</th>
                    <td>';
            // line 9
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['value'], 'html', null, true);
            yield '</td>
                </tr>
            ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['label'], $context['value'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 12
        yield '        </tbody>
    </table>

    <h2>';
        // line 15
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Plugins', 'app'), 'html', null, true);
        yield '</h2>

    ';
        // line 17
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['plugins']) || array_key_exists('plugins', $context) ? $context['plugins'] : (function () {
            throw new RuntimeError('Variable "plugins" does not exist.', 17, $this->source);
        })()))) {
            // line 18
            yield '        <table class="data fullwidth fixed-layout" dir="ltr">
            <tbody>
                ';
            // line 20
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['plugins']) || array_key_exists('plugins', $context) ? $context['plugins'] : (function () {
                throw new RuntimeError('Variable "plugins" does not exist.', 20, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['plugin']) {
                // line 21
                yield '                    <tr>
                        <th class="light">';
                // line 22
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['plugin'], 'name', [], 'any', false, false, false, 22), 'html', null, true);
                yield '</th>
                        <td>';
                // line 23
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['plugin'], 'version', [], 'any', false, false, false, 23), 'html', null, true);
                yield '</td>
                    </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['plugin'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 26
            yield '            </tbody>
        </table>
    ';
        } else {
            // line 29
            yield '        <p>';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('No plugins are enabled.', 'app'), 'html', null, true);
            yield '</p>
    ';
        }
        // line 31
        yield '
    <h2>';
        // line 32
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Modules', 'app'), 'html', null, true);
        yield '</h2>

    ';
        // line 34
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['modules']) || array_key_exists('modules', $context) ? $context['modules'] : (function () {
            throw new RuntimeError('Variable "modules" does not exist.', 34, $this->source);
        })()))) {
            // line 35
            yield '        <table class="data fullwidth fixed-layout" dir="ltr">
            <tbody>
                ';
            // line 37
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['modules']) || array_key_exists('modules', $context) ? $context['modules'] : (function () {
                throw new RuntimeError('Variable "modules" does not exist.', 37, $this->source);
            })()));
            foreach ($context['_seq'] as $context['id'] => $context['class']) {
                // line 38
                yield '                    <tr>
                        <th class="light">';
                // line 39
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['id'], 'html', null, true);
                yield '</th>
                        <td>';
                // line 40
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['class'], 'html', null, true);
                yield '</td>
                    </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['id'], $context['class'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 43
            yield '            </tbody>
        </table>
    ';
        } else {
            // line 46
            yield '        <p>';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('No modules are installed.', 'app'), 'html', null, true);
            yield '</p>
    ';
        }
        // line 48
        yield '
  <h2>';
        // line 49
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Aliases', 'app'), 'html', null, true);
        yield '</h2>
  <p>
      ';
        // line 51
        yield $this->extensions['craft\web\twig\Extension']->translateFilter('The following <a href="{url}">aliases</a> are defined:', 'app', ['url' => 'https://craftcms.com/docs/5.x/configure.html#aliases']);
        // line 53
        yield '
  </p>

  <table class="data fullwidth fixed-layout" dir="ltr">
      <tbody>
          ';
        // line 58
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['aliases']) || array_key_exists('aliases', $context) ? $context['aliases'] : (function () {
            throw new RuntimeError('Variable "aliases" does not exist.', 58, $this->source);
        })()));
        foreach ($context['_seq'] as $context['alias'] => $context['value']) {
            // line 59
            yield '              <tr>
                  <th class="light">';
            // line 60
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['alias'], 'html', null, true);
            yield '</th>
                  <td>';
            // line 61
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['value'], 'html', null, true);
            yield '</td>
              </tr>
          ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['alias'], $context['value'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 64
        yield '      </tbody>
  </table>

    <h2>';
        // line 67
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Requirements', 'app'), 'html', null, true);
        yield '</h2>

    <table class="data fullwidth" dir="ltr">
        <tbody>
        ';
        // line 71
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['requirements']) || array_key_exists('requirements', $context) ? $context['requirements'] : (function () {
            throw new RuntimeError('Variable "requirements" does not exist.', 71, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['requirement']) {
            // line 72
            yield '            <tr>
                <td class="thin centeralign">
                    ';
            // line 74
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['requirement'], 'error', [], 'any', false, false, false, 74)) {
                // line 75
                yield '                        <span class="error" title="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Failed', 'app'), 'html', null, true);
                yield '" aria-label="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Failed', 'app'), 'html', null, true);
                yield '" data-icon="error"></span>
                    ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 76
                $context['requirement'], 'warning', [], 'any', false, false, false, 76)) {
                // line 77
                yield '                        <span class="warning" title="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Passed with warning', 'app'), 'html', null, true);
                yield '" aria-label="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Passed with warning', 'app'), 'html', null, true);
                yield '" data-icon="alert"></span>
                    ';
            } else {
                // line 79
                yield '                        <span class="success" title="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Passed', 'app'), 'html', null, true);
                yield '" aria-label="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Passed', 'app'), 'html', null, true);
                yield '" data-icon="check"></span>
                    ';
            }
            // line 81
            yield '                </td>
                <td>';
            // line 82
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['requirement'], 'name', [], 'any', false, false, false, 82), 'html', null, true);
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['requirement'], 'memo', [], 'any', false, false, false, 82)) {
                yield ' <span class="info">';
                yield craft\helpers\Template::attribute($this->env, $this->source, $context['requirement'], 'memo', [], 'any', false, false, false, 82);
                yield '</span>';
            }
            yield '</td>
            </tr>
        ';
        }
        unset($context['_seq'], $context['_key'], $context['requirement'], $context['_parent']);
        // line 85
        yield '        </tbody>
    </table>
</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/SystemReport.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/SystemReport.twig';
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
        return [266 => 85,  252 => 82,  249 => 81,  241 => 79,  233 => 77,  231 => 76,  224 => 75,  222 => 74,  218 => 72,  214 => 71,  207 => 67,  202 => 64,  193 => 61,  189 => 60,  186 => 59,  182 => 58,  175 => 53,  173 => 51,  168 => 49,  165 => 48,  159 => 46,  154 => 43,  145 => 40,  141 => 39,  138 => 38,  134 => 37,  130 => 35,  128 => 34,  123 => 32,  120 => 31,  114 => 29,  109 => 26,  100 => 23,  96 => 22,  93 => 21,  89 => 20,  85 => 18,  83 => 17,  78 => 15,  73 => 12,  64 => 9,  60 => 8,  57 => 7,  53 => 6,  46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
          url: 'https://craftcms.com/docs/5.x/configure.html#aliases',
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
", '_components/utilities/SystemReport.twig', '/tmp/packages/craft5/src/templates/_components/utilities/SystemReport.twig');
    }
}
