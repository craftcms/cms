<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _ui/nav-item.twig */
class __TwigTemplate_f6732b205db42268c3f6198616c28ab1 extends Template
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
        craft\helpers\Template::beginProfile('template', '_ui/nav-item.twig');
        // line 1
        echo '<div ';
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['attributes']) || array_key_exists('attributes', $context) ? $context['attributes'] : (function () {
            throw new RuntimeError('Variable "attributes" does not exist.', 1, $this->source);
        })()), 'class', [0 =>         // line 2
(isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
    throw new RuntimeError('Variable "type" does not exist.', 2, $this->source);
})()), ], 'method'), 'merge', [0 => ['data-component' =>         // line 3
(isset($context['handle']) || array_key_exists('handle', $context) ? $context['handle'] : (function () {
    throw new RuntimeError('Variable "handle" does not exist.', 3, $this->source);
})()), ]], 'method'), 'html', null, true);
        // line 4
        echo '>
    ';
        // line 5
        if (((isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
            throw new RuntimeError('Variable "type" does not exist.', 5, $this->source);
        })()) == 'heading')) {
            // line 6
            echo '        <span>';
            echo twig_escape_filter($this->env, (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                throw new RuntimeError('Variable "label" does not exist.', 6, $this->source);
            })()), 'html', null, true);
            echo '</span>
    ';
        } else {
            // line 8
            echo '        <a
            href="';
            // line 9
            echo twig_escape_filter($this->env, (isset($context['url']) || array_key_exists('url', $context) ? $context['url'] : (function () {
                throw new RuntimeError('Variable "url" does not exist.', 9, $this->source);
            })()), 'html', null, true);
            echo '"
            class="';
            // line 10
            if ((isset($context['external']) || array_key_exists('external', $context) ? $context['external'] : (function () {
                throw new RuntimeError('Variable "external" does not exist.', 10, $this->source);
            })())) {
                echo 'external';
            }
            echo ' ';
            if (((isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                throw new RuntimeError('Variable "selected" does not exist.', 10, $this->source);
            })()) && ! $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
                throw new RuntimeError('Variable "items" does not exist.', 10, $this->source);
            })())))) {
                echo 'sel';
            }
            echo '"
            ';
            // line 11
            if ((isset($context['external']) || array_key_exists('external', $context) ? $context['external'] : (function () {
                throw new RuntimeError('Variable "external" does not exist.', 11, $this->source);
            })())) {
                echo 'target="_blank" rel="nofollow noopener"';
            }
            // line 12
            echo '        >
            ';
            // line 13
            echo isset($context['icon']) || array_key_exists('icon', $context) ? $context['icon'] : (function () {
                throw new RuntimeError('Variable "icon" does not exist.', 13, $this->source);
            })();
            echo '

            <span class="label">';
            // line 15
            echo twig_escape_filter($this->env, (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                throw new RuntimeError('Variable "label" does not exist.', 15, $this->source);
            })()), 'html', null, true);
            echo '</span>';
            // line 17
            if ((isset($context['badgeCount']) || array_key_exists('badgeCount', $context) ? $context['badgeCount'] : (function () {
                throw new RuntimeError('Variable "badgeCount" does not exist.', 17, $this->source);
            })())) {
                // line 18
                echo '<span class="badge" aria-hidden="true">';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->numberFilter((isset($context['badgeCount']) || array_key_exists('badgeCount', $context) ? $context['badgeCount'] : (function () {
                    throw new RuntimeError('Variable "badgeCount" does not exist.', 18, $this->source);
                })()), 0), 'html', null, true);
                echo '</span>
                ';
                // line 19
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'data' => ['notification' => true], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('{num, number} {num, plural, =1{notification} other{notifications}}', 'app', ['num' =>                 // line 25
(isset($context['badgeCount']) || array_key_exists('badgeCount', $context) ? $context['badgeCount'] : (function () {
    throw new RuntimeError('Variable "badgeCount" does not exist.', 25, $this->source);
})()), ])]);
            }
            // line 29
            echo '</a>
    ';
        }
        // line 31
        echo '
    ';
        // line 32
        if (((isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
            throw new RuntimeError('Variable "selected" does not exist.', 32, $this->source);
        })()) && $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 32, $this->source);
        })())))) {
            // line 33
            echo '        <ul class="subnav">
            ';
            // line 34
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
                throw new RuntimeError('Variable "items" does not exist.', 34, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['item']) {
                // line 35
                echo '                <li>
                    ';
                // line 36
                echo $context['item'];
                echo '
                </li>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 39
            echo '        </ul>
    ';
        }
        // line 41
        echo '</div>';
        craft\helpers\Template::endProfile('template', '_ui/nav-item.twig');
    }

    public function getTemplateName()
    {
        return '_ui/nav-item.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [129 => 41,  125 => 39,  116 => 36,  113 => 35,  109 => 34,  106 => 33,  104 => 32,  101 => 31,  97 => 29,  94 => 25,  93 => 19,  88 => 18,  86 => 17,  83 => 15,  78 => 13,  75 => 12,  71 => 11,  61 => 10,  57 => 9,  54 => 8,  48 => 6,  46 => 5,  43 => 4,  41 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div {{ attributes.class(
    type).merge({
    'data-component': handle
}) }}>
    {% if type == 'heading' %}
        <span>{{ label }}</span>
    {% else %}
        <a
            href=\"{{ url }}\"
            class=\"{% if external %}external{% endif %} {% if selected and not items | length %}sel{% endif %}\"
            {% if external %}target=\"_blank\" rel=\"nofollow noopener\"{% endif %}
        >
            {{ icon | raw }}

            <span class=\"label\">{{ label }}</span>

            {%- if badgeCount -%}
                <span class=\"badge\" aria-hidden=\"true\">{{ badgeCount|number(decimals=0) }}</span>
                {{ tag('span', {
                    class: 'visually-hidden',
                    data: {
                        notification: true,
                    },
                    text: '{num, number} {num, plural, =1{notification} other{notifications}}'|t('app', {
                        num: badgeCount,
                    }),
                }) }}
            {%- endif -%}
        </a>
    {% endif %}

    {% if selected and items | length %}
        <ul class=\"subnav\">
            {% for item in items %}
                <li>
                    {{ item | raw }}
                </li>
            {% endfor %}
        </ul>
    {% endif %}
</div>", '_ui/nav-item.twig', '/Users/brianhanson/Development/craft5/src/templates/_ui/nav-item.twig');
    }
}
