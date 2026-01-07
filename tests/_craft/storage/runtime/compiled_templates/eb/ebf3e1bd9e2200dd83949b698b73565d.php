<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/notifications */
class __TwigTemplate_54d8103c932da17647613e655c9392d7 extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/notifications');
        // line 1
        echo '<div id="notifications" role="status">
  <h2 id="cp-notification-heading" class="visually-hidden">';
        // line 2
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Notifications', 'app'), 'html', null, true);
        echo '</h2>
</div>

';
        // line 5
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable([0 => 'notice', 1 => 'success', 2 => 'error']);
        foreach ($context['_seq'] as $context['_key'] => $context['type']) {
            // line 6
            echo '  ';
            $context['notification'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 6, $this->source);
            })()), 'app', []), 'session', []), 'getFlash', [0 => ('cp-notification-'.$context['type'])], 'method');
            // line 7
            echo '  ';
            if ((isset($context['notification']) || array_key_exists('notification', $context) ? $context['notification'] : (function () {
                throw new RuntimeError('Variable "notification" does not exist.', 7, $this->source);
            })())) {
                // line 8
                echo '    ';
                ob_start();
                // line 9
                echo '      Craft.cp.displayNotification(';
                echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($context['type']);
                echo ', ';
                echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['notification']) || array_key_exists('notification', $context) ? $context['notification'] : (function () {
                    throw new RuntimeError('Variable "notification" does not exist.', 9, $this->source);
                })()), 0, [], 'array'));
                echo ', ';
                echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['notification']) || array_key_exists('notification', $context) ? $context['notification'] : (function () {
                    throw new RuntimeError('Variable "notification" does not exist.', 9, $this->source);
                })()), 1, [], 'array'));
                echo ');
    ';
                craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
                // line 11
                echo '  ';
            }
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['type'], $context['_parent'], $context['loop']);
        craft\helpers\Template::endProfile('template', '_layouts/components/notifications');
    }

    public function getTemplateName()
    {
        return '_layouts/components/notifications';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [70 => 11,  60 => 9,  57 => 8,  54 => 7,  51 => 6,  47 => 5,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div id=\"notifications\" role=\"status\">
  <h2 id=\"cp-notification-heading\" class=\"visually-hidden\">{{ 'Notifications'|t('app') }}</h2>
</div>

{% for type in ['notice', 'success', 'error'] %}
  {% set notification = craft.app.session.getFlash(\"cp-notification-#{type}\") %}
  {% if notification %}
    {% js %}
      Craft.cp.displayNotification({{ type|json_encode|raw }}, {{ notification[0]|json_encode|raw }}, {{ notification[1]|json_encode|raw }});
    {% endjs %}
  {% endif %}
{% endfor %}
", '_layouts/components/notifications', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/notifications.twig');
    }
}
