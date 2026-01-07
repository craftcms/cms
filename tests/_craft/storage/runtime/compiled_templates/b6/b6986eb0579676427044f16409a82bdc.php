<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/nav */
class __TwigTemplate_cbb90ea09638b206418d2d59453a9a52 extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_includes/nav');
        // line 27
        echo '
';
        // line 28
        ob_start();
        // line 33
        echo '  ';
        echo twig_call_macro($macros['_self'], 'macro_list', [(isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 33, $this->source);
        })()), (($context['selectedItem']) ?? (null))], 33, $context, $this->getSourceContext());
        echo '
';
        echo craft\helpers\Html::tag('nav', ob_get_clean(), ['aria' => ['label' => ((        // line 30
            $context['label']) ?? (false))]]);
        craft\helpers\Template::endProfile('template', '_includes/nav');
    }

    // line 1
    public function macro_list($__items__ = null, $__selectedItem__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'items' => $__items__,
            'selectedItem' => $__selectedItem__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'list');
            // line 2
            echo '  <ul>
    ';
            // line 3
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
                throw new RuntimeError('Variable "items" does not exist.', 3, $this->source);
            })()));
            foreach ($context['_seq'] as $context['key'] => $context['item']) {
                // line 4
                echo '      ';
                if ($context['item']) {
                    // line 5
                    echo '        ';
                    ob_start();
                    // line 10
                    echo '          ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'heading', [], 'any', true, true)) {
                        // line 11
                        echo '            <span>';
                        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'heading', []), 'html', null, true);
                        echo '</span>
            ';
                        // line 12
                        echo twig_call_macro($macros['_self'], 'macro_list', [craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'nested', [])], 12, $context, $this->getSourceContext());
                        echo '
          ';
                    } else {
                        // line 14
                        echo '            ';
                        echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['class' => twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['sel' => ((((craft\helpers\Template::attribute($this->env, $this->source,                         // line 16
                            $context['item'], 'selected', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'selected', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'selected', [])) : (false)) || ((isset($context['selectedItem']) || array_key_exists('selectedItem', $context) ? $context['selectedItem'] : (function () {
                                throw new RuntimeError('Variable "selectedItem" does not exist.', 16, $this->source);
                            })()) && ($context['key'] == (isset($context['selectedItem']) || array_key_exists('selectedItem', $context) ? $context['selectedItem'] : (function () {
                                throw new RuntimeError('Variable "selectedItem" does not exist.', 16, $this->source);
                            })()))))])), 'href' => craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source,                         // line 18
                                $context['item'], 'url', [])), 'text' => craft\helpers\Template::attribute($this->env, $this->source,                         // line 19
                                    $context['item'], 'label', [])]);
                        // line 20
                        echo '
          ';
                    }
                    // line 22
                    echo '        ';
                    echo craft\helpers\Html::tag('li', ob_get_clean(), ['class' => twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['heading' => craft\helpers\Template::attribute($this->env, $this->source,                     // line 7
                        $context['item'], 'heading', [], 'any', true, true)]))]);
                    // line 23
                    echo '      ';
                }
                // line 24
                echo '    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['key'], $context['item'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 25
            echo '  </ul>
';
            craft\helpers\Template::endProfile('macro', 'list');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_includes/nav';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [118 => 25,  112 => 24,  109 => 23,  107 => 7,  105 => 22,  101 => 20,  99 => 19,  98 => 18,  97 => 16,  95 => 14,  90 => 12,  85 => 11,  82 => 10,  79 => 5,  76 => 4,  72 => 3,  69 => 2,  54 => 1,  49 => 30,  44 => 33,  42 => 28,  39 => 27];
    }

    public function getSourceContext()
    {
        return new Source("{% macro list(items, selectedItem) %}
  <ul>
    {% for key, item in items %}
      {% if item %}
        {% tag 'li' with {
          class: {
            heading: item.heading is defined,
          }|filter|keys,
        } %}
          {% if item.heading is defined %}
            <span>{{ item.heading }}</span>
            {{ _self.list(item.nested) }}
          {% else %}
            {{ tag('a', {
              class: {
                sel: (item.selected ?? false) or (selectedItem and key == selectedItem),
              }|filter|keys,
              href: url(item.url),
              text: item.label,
            }) }}
          {% endif %}
        {% endtag %}
      {% endif %}
    {% endfor %}
  </ul>
{% endmacro %}

{% tag 'nav' with {
  aria: {
    label: label ?? false,
  },
} %}
  {{ _self.list(items, selectedItem ?? null) }}
{% endtag %}
", '_includes/nav', '/Users/brianhanson/Development/craft5/src/templates/_includes/nav.twig');
    }
}
