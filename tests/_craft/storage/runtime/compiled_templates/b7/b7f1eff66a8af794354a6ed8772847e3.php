<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/alerts */
class __TwigTemplate_7a19b71a3a1d6c1f1d1bd7ffa502f221 extends Template
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
                $this->loadTemplate('_layouts/components/alerts', '_layouts/components/alerts', 4, '217358508')->display(twig_to_array(['alerts' =>                 // line 5
(isset($context['alerts']) || array_key_exists('alerts', $context) ? $context['alerts'] : (function () {
    throw new RuntimeError('Variable "alerts" does not exist.', 5, $this->source);
})()), 'type' => 'ul', 'attributes' => ['id' => 'alerts'], 'style' => ['display' => 'block', 'position' => 'relative', 'background-color' => 'var(--red-050)', 'border-left' => '6px solid var(--error-color)', 'color' => 'var(--error-color)'], ]));
                // line 43
                echo '  ';
            }
        } else {
            // line 45
            echo '  ';
            ob_start();
            // line 46
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
        return [57 => 46,  54 => 45,  50 => 43,  48 => 5,  46 => 4,  43 => 3,  40 => 2,  38 => 1];
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
class __TwigTemplate_7a19b71a3a1d6c1f1d1bd7ffa502f221___217358508 extends Template
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
            $this->loadTemplate('_layouts/components/alerts', '_layouts/components/alerts', 20, '698262843')->display(twig_to_array(['alert' =>             // line 21
$context['alert'], 'type' => 'li', 'style' => ['display' => 'block'], ]));
            // line 40
            echo '        ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['alert'], $context['_parent'], $context['loop']);
        // line 41
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
        return [191 => 41,  185 => 40,  183 => 21,  181 => 20,  176 => 19,  171 => 18,  158 => 4,  57 => 46,  54 => 45,  50 => 43,  48 => 5,  46 => 4,  43 => 3,  40 => 2,  38 => 1];
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
class __TwigTemplate_7a19b71a3a1d6c1f1d1bd7ffa502f221___698262843 extends Template
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

    // line 27
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 28
        echo '              ';
        if (($this->env->getTest('string')->getCallable()((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 28, $this->source);
        })())) || (((craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['alert'] ?? null), 'showIcon', [])) : (true)))) {
            // line 29
            echo '                ';
            $this->loadTemplate('_layouts/components/tag.twig', '_layouts/components/alerts', 29)->display(twig_to_array(['type' => 'span', 'attributes' => ['aria-label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Error', 'app'), 'data-icon' => 'alert']]));
            // line 36
            echo '              ';
        }
        // line 37
        echo '              ';
        echo ($this->env->getTest('array')->getCallable()((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 37, $this->source);
        })()))) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 37, $this->source);
        })()), 'content', [])) : ((isset($context['alert']) || array_key_exists('alert', $context) ? $context['alert'] : (function () {
            throw new RuntimeError('Variable "alert" does not exist.', 37, $this->source);
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
        return [314 => 37,  311 => 36,  308 => 29,  305 => 28,  300 => 27,  287 => 20,  191 => 41,  185 => 40,  183 => 21,  181 => 20,  176 => 19,  171 => 18,  158 => 4,  57 => 46,  54 => 45,  50 => 43,  48 => 5,  46 => 4,  43 => 3,  40 => 2,  38 => 1];
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
