<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/alerts */
class __TwigTemplate_d7ee3fd3af5108333e747be27b439dc3 extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/alerts');
        // line 1
        if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'cp', []), 'areAlertsCached', [], 'method')) {
            // line 2
            echo '  ';
            $context['alerts'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 2, $this->source);
            })()), 'cp', []), 'getAlerts', [], 'method');
            // line 3
            echo '  ';
            if ((isset($context['alerts']) || array_key_exists('alerts', $context) ? $context['alerts'] : (function () {
                throw new RuntimeError('Variable "alerts" does not exist.', 3, $this->source);
            })())) {
                // line 4
                echo '    ';
                $this->loadTemplate('_layouts/components/alerts', '_layouts/components/alerts', 4, '1942662870')->display(twig_to_array(['alerts' =>                 // line 5
(isset($context['alerts']) || array_key_exists('alerts', $context) ? $context['alerts'] : (function () {
    throw new RuntimeError('Variable "alerts" does not exist.', 5, $this->source);
})()), 'type' => 'ul', 'attributes' => ['id' => 'alerts'], 'style' => ['display' => 'block', 'position' => 'relative', 'background-color' => 'var(--red-050)', 'border-left' => '6px solid var(--error-color)', 'color' => 'var(--error-color)'], ]));
                // line 44
                echo '  ';
            }
        } else {
            // line 46
            echo '  ';
            ob_start();
            // line 47
            echo '    Craft.cp.fetchAlerts().then(alerts => {
      Craft.cp.displayAlerts(alerts);
    });
  ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        craft\helpers\Template::endProfile('template', '_layouts/components/alerts');
    }

    public function getTemplateName()
    {
        return '_layouts/components/alerts';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [57 => 47,  54 => 46,  50 => 44,  48 => 5,  46 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
              'height': 'var(--header-height)'
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
", '_layouts/components/alerts', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/alerts.twig');
    }
}

/* _layouts/components/alerts */
class __TwigTemplate_d7ee3fd3af5108333e747be27b439dc3___1942662870 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 4
        return '_layouts/components/tag.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_layouts/components/alerts');
        $this->parent = $this->loadTemplate('_layouts/components/tag.twig', '_layouts/components/alerts', 4);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/components/alerts');
    }

    // line 18
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 19
        echo '        ';
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['alerts']) || array_key_exists('alerts', $context) ? $context['alerts'] : (function () {
            throw new RuntimeError('Variable "alerts" does not exist.', 19, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['alert']) {
            // line 20
            echo '          ';
            $this->loadTemplate('_layouts/components/alerts', '_layouts/components/alerts', 20, '1140168402')->display(twig_to_array(['alert' =>             // line 21
$context['alert'], 'type' => 'li', 'style' => ['display' => 'block', 'height' => 'var(--header-height)'], ]));
            // line 41
            echo '        ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['alert'], $context['_parent'], $context['loop']);
        // line 42
        echo '      ';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return '_layouts/components/alerts';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [192 => 42,  186 => 41,  184 => 21,  182 => 20,  177 => 19,  172 => 18,  159 => 4,  57 => 47,  54 => 46,  50 => 44,  48 => 5,  46 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
              'height': 'var(--header-height)'
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
", '_layouts/components/alerts', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/alerts.twig');
    }
}

/* _layouts/components/alerts */
class __TwigTemplate_d7ee3fd3af5108333e747be27b439dc3___1140168402 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 20
        return '_layouts/components/tag.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_layouts/components/alerts');
        $this->parent = $this->loadTemplate('_layouts/components/tag.twig', '_layouts/components/alerts', 20);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/components/alerts');
    }

    // line 28
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 29
        echo '              ';
        if (($this->env->getTest('string')->getCallable()((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 29, $this->source);
        })())) || (((craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', [])) : (true)))) {
            // line 30
            echo '                ';
            $this->loadTemplate('_layouts/components/tag.twig', '_layouts/components/alerts', 30)->display(twig_to_array(['type' => 'span', 'attributes' => ['aria-label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Error', 'app'), 'data-icon' => 'alert']]));
            // line 37
            echo '              ';
        }
        // line 38
        echo '              ';
        echo ($this->env->getTest('array')->getCallable()((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 38, $this->source);
        })()))) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 38, $this->source);
        })()), 'content', [])) : ((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 38, $this->source);
        })()));
        echo '
            ';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return '_layouts/components/alerts';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [316 => 38,  313 => 37,  310 => 30,  307 => 29,  302 => 28,  289 => 20,  192 => 42,  186 => 41,  184 => 21,  182 => 20,  177 => 19,  172 => 18,  159 => 4,  57 => 47,  54 => 46,  50 => 44,  48 => 5,  46 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
              'height': 'var(--header-height)'
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
", '_layouts/components/alerts', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/alerts.twig');
    }
}
