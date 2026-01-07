<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _layouts/components/notifications */
class __TwigTemplate_e04340a714f5f2e1e5d8d527bdbc2ff9 extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/notifications');
        // line 1
        yield '<div id="notifications" role="status">
  <h2 id="cp-notification-heading" class="visually-hidden hidden">';
        // line 2
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Notifications', 'app'), 'html', null, true);
        yield '</h2>
</div>

';
        // line 5
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(['notice', 'success', 'error']);
        foreach ($context['_seq'] as $context['_key'] => $context['type']) {
            // line 6
            yield '  ';
            $context['notification'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 6, $this->source);
            })()), 'app', [], 'any', false, false, false, 6), 'session', [], 'any', false, false, false, 6), 'getFlash', [('cp-notification-'.$context['type'])], 'method', false, false, false, 6);
            // line 7
            yield '  ';
            if ((isset($context['notification']) || array_key_exists('notification', $context) ? $context['notification'] : (function () {
                throw new RuntimeError('Variable "notification" does not exist.', 7, $this->source);
            })())) {
                // line 8
                yield '    ';
                ob_start();
                // line 9
                yield '      Craft.cp.displayNotification(';
                yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($context['type']);
                yield ', ';
                yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['notification']) || array_key_exists('notification', $context) ? $context['notification'] : (function () {
                    throw new RuntimeError('Variable "notification" does not exist.', 9, $this->source);
                })()), 0, [], 'array', false, false, false, 9));
                yield ', ';
                yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['notification']) || array_key_exists('notification', $context) ? $context['notification'] : (function () {
                    throw new RuntimeError('Variable "notification" does not exist.', 9, $this->source);
                })()), 1, [], 'array', false, false, false, 9));
                yield ');
    ';
                craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
                // line 11
                yield '  ';
            }
        }
        unset($context['_seq'], $context['_key'], $context['type'], $context['_parent']);
        craft\helpers\Template::endProfile('template', '_layouts/components/notifications');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/notifications';
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
        return [75 => 11,  65 => 9,  62 => 8,  59 => 7,  56 => 6,  52 => 5,  46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("<div id=\"notifications\" role=\"status\">
  <h2 id=\"cp-notification-heading\" class=\"visually-hidden hidden\">{{ 'Notifications'|t('app') }}</h2>
</div>

{% for type in ['notice', 'success', 'error'] %}
  {% set notification = craft.app.session.getFlash(\"cp-notification-#{type}\") %}
  {% if notification %}
    {% js %}
      Craft.cp.displayNotification({{ type|json_encode|raw }}, {{ notification[0]|json_encode|raw }}, {{ notification[1]|json_encode|raw }});
    {% endjs %}
  {% endif %}
{% endfor %}
", '_layouts/components/notifications', '/tmp/packages/craft5/src/templates/_layouts/components/notifications.twig');
    }
}
