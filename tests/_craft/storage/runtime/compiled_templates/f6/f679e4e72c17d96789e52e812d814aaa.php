<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* _layouts/components/alerts */
class __TwigTemplate_ceb06333be56d58becda72fd054807e9 extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/alerts');
        // line 1
        if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'cp', [], 'any', false, false, false, 1), 'areAlertsCached', [], 'method', false, false, false, 1)) {
            // line 2
            yield '  ';
            $context['alerts'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 2, $this->source);
            })()), 'cp', [], 'any', false, false, false, 2), 'getAlerts', [], 'method', false, false, false, 2);
            // line 3
            yield '  ';
            if ((isset($context['alerts']) || array_key_exists('alerts', $context) ? $context['alerts'] : (function () {
                throw new RuntimeError('Variable "alerts" does not exist.', 3, $this->source);
            })())) {
                // line 4
                yield '    ';
                yield from $this->loadTemplate('_layouts/components/alerts', '_layouts/components/alerts', 4, '421868642')->unwrap()->yield(CoreExtension::toArray(['alerts' =>                 // line 5
(isset($context['alerts']) || array_key_exists('alerts', $context) ? $context['alerts'] : (function () {
    throw new RuntimeError('Variable "alerts" does not exist.', 5, $this->source);
})()), 'type' => 'ul', 'attributes' => ['id' => 'alerts'], 'style' => ['display' => 'block', 'position' => 'relative', 'background-color' => 'var(--red-050)', 'border-left' => '6px solid var(--error-color)', 'color' => 'var(--error-color)']]));
                // line 44
                yield '  ';
            }
        } else {
            // line 46
            yield '  ';
            ob_start();
            // line 47
            yield '    Craft.cp.fetchAlerts().then(alerts => {
      Craft.cp.displayAlerts(alerts);
    });
  ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        craft\helpers\Template::endProfile('template', '_layouts/components/alerts');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/alerts';
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
        return [62 => 47,  59 => 46,  55 => 44,  53 => 5,  51 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% if craft.cp.areAlertsCached() %}
  {% set alerts = craft.cp.getAlerts() %}
  {% if alerts %}
    {% embed '_layouts/components/tag.twig' with {
      alerts: alerts,
      type: 'ul',
      attributes: {
        id: 'alerts',
      },
      style: {
        'display': 'block',
        'position': 'relative',
        'background-color': 'var(--red-050)',
        'border-left': '6px solid var(--error-color)',
        'color': 'var(--error-color)',
      },
    } only %}
      {% block content %}
        {% for alert in alerts %}
          {% embed '_layouts/components/tag.twig' with {
            alert: alert,
            type: 'li',
            style: {
              'display': 'block',
              'min-height': 'var(--header-height)'
            },
          } only %}
            {% block content %}
              {% if alert is string or alert.showIcon ?? true %}
                {% include '_layouts/components/tag.twig' with {
                  type: 'span',
                  attributes: {
                    'aria-label': 'Error'|t('app'),
                    'data-icon': 'alert',
                  },
                } only %}
              {% endif %}
              {{ (alert is array ? alert.content : alert)|raw }}
            {% endblock %}
          {% endembed %}
        {% endfor %}
      {% endblock %}
    {% endembed %}
  {% endif %}
{% else %}
  {% js %}
    Craft.cp.fetchAlerts().then(alerts => {
      Craft.cp.displayAlerts(alerts);
    });
  {% endjs %}
{% endif %}
", '_layouts/components/alerts', '/tmp/packages/craft5/src/templates/_layouts/components/alerts.twig');
    }
}

/* _layouts/components/alerts */
class __TwigTemplate_ceb06333be56d58becda72fd054807e9___421868642 extends Template
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
        // line 4
        return '_layouts/components/tag.twig';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_layouts/components/alerts');
        $this->parent = $this->loadTemplate('_layouts/components/tag.twig', '_layouts/components/alerts', 4);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/components/alerts');
    }

    // line 18
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 19
        yield '        ';
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['alerts']) || array_key_exists('alerts', $context) ? $context['alerts'] : (function () {
            throw new RuntimeError('Variable "alerts" does not exist.', 19, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['alert']) {
            // line 20
            yield '          ';
            yield from $this->loadTemplate('_layouts/components/alerts', '_layouts/components/alerts', 20, '1740528410')->unwrap()->yield(CoreExtension::toArray(['alert' =>             // line 21
$context['alert'], 'type' => 'li', 'style' => ['display' => 'block', 'min-height' => 'var(--header-height)']]));
            // line 41
            yield '        ';
        }
        unset($context['_seq'], $context['_key'], $context['alert'], $context['_parent']);
        // line 42
        yield '      ';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/alerts';
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
        return [213 => 42,  207 => 41,  205 => 21,  203 => 20,  198 => 19,  190 => 18,  177 => 4,  62 => 47,  59 => 46,  55 => 44,  53 => 5,  51 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% if craft.cp.areAlertsCached() %}
  {% set alerts = craft.cp.getAlerts() %}
  {% if alerts %}
    {% embed '_layouts/components/tag.twig' with {
      alerts: alerts,
      type: 'ul',
      attributes: {
        id: 'alerts',
      },
      style: {
        'display': 'block',
        'position': 'relative',
        'background-color': 'var(--red-050)',
        'border-left': '6px solid var(--error-color)',
        'color': 'var(--error-color)',
      },
    } only %}
      {% block content %}
        {% for alert in alerts %}
          {% embed '_layouts/components/tag.twig' with {
            alert: alert,
            type: 'li',
            style: {
              'display': 'block',
              'min-height': 'var(--header-height)'
            },
          } only %}
            {% block content %}
              {% if alert is string or alert.showIcon ?? true %}
                {% include '_layouts/components/tag.twig' with {
                  type: 'span',
                  attributes: {
                    'aria-label': 'Error'|t('app'),
                    'data-icon': 'alert',
                  },
                } only %}
              {% endif %}
              {{ (alert is array ? alert.content : alert)|raw }}
            {% endblock %}
          {% endembed %}
        {% endfor %}
      {% endblock %}
    {% endembed %}
  {% endif %}
{% else %}
  {% js %}
    Craft.cp.fetchAlerts().then(alerts => {
      Craft.cp.displayAlerts(alerts);
    });
  {% endjs %}
{% endif %}
", '_layouts/components/alerts', '/tmp/packages/craft5/src/templates/_layouts/components/alerts.twig');
    }
}

/* _layouts/components/alerts */
class __TwigTemplate_ceb06333be56d58becda72fd054807e9___1740528410 extends Template
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
        // line 20
        return '_layouts/components/tag.twig';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_layouts/components/alerts');
        $this->parent = $this->loadTemplate('_layouts/components/tag.twig', '_layouts/components/alerts', 20);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/components/alerts');
    }

    // line 28
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 29
        yield '              ';
        if (($this->env->getTest('string')->getCallable()((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 29, $this->source);
        })())) || (((craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', [], 'any', true, true, false, 29) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', [], 'any', false, false, false, 29) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', [], 'any', false, false, false, 29)) : (true)))) {
            // line 30
            yield '                ';
            yield from $this->loadTemplate('_layouts/components/tag.twig', '_layouts/components/alerts', 30)->unwrap()->yield(CoreExtension::toArray(['type' => 'span', 'attributes' => ['aria-label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Error', 'app'), 'data-icon' => 'alert']]));
            // line 37
            yield '              ';
        }
        // line 38
        yield '              ';
        yield ($this->env->getTest('array')->getCallable()((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 38, $this->source);
        })()))) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 38, $this->source);
        })()), 'content', [], 'any', false, false, false, 38)) : ((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 38, $this->source);
        })()));
        yield '
            ';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/alerts';
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
        return [353 => 38,  350 => 37,  347 => 30,  344 => 29,  336 => 28,  323 => 20,  213 => 42,  207 => 41,  205 => 21,  203 => 20,  198 => 19,  190 => 18,  177 => 4,  62 => 47,  59 => 46,  55 => 44,  53 => 5,  51 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% if craft.cp.areAlertsCached() %}
  {% set alerts = craft.cp.getAlerts() %}
  {% if alerts %}
    {% embed '_layouts/components/tag.twig' with {
      alerts: alerts,
      type: 'ul',
      attributes: {
        id: 'alerts',
      },
      style: {
        'display': 'block',
        'position': 'relative',
        'background-color': 'var(--red-050)',
        'border-left': '6px solid var(--error-color)',
        'color': 'var(--error-color)',
      },
    } only %}
      {% block content %}
        {% for alert in alerts %}
          {% embed '_layouts/components/tag.twig' with {
            alert: alert,
            type: 'li',
            style: {
              'display': 'block',
              'min-height': 'var(--header-height)'
            },
          } only %}
            {% block content %}
              {% if alert is string or alert.showIcon ?? true %}
                {% include '_layouts/components/tag.twig' with {
                  type: 'span',
                  attributes: {
                    'aria-label': 'Error'|t('app'),
                    'data-icon': 'alert',
                  },
                } only %}
              {% endif %}
              {{ (alert is array ? alert.content : alert)|raw }}
            {% endblock %}
          {% endembed %}
        {% endfor %}
      {% endblock %}
    {% endembed %}
  {% endif %}
{% else %}
  {% js %}
    Craft.cp.fetchAlerts().then(alerts => {
      Craft.cp.displayAlerts(alerts);
    });
  {% endjs %}
{% endif %}
", '_layouts/components/alerts', '/tmp/packages/craft5/src/templates/_layouts/components/alerts.twig');
    }
}
