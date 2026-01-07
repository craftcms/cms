<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/nav */
class __TwigTemplate_4646cd5d98d8a5bf6f06790c60f63295 extends Template
{
    private readonly Source $source;

    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_includes/nav');
        // line 27
        yield '
';
        // line 28
        ob_start();
        // line 33
        yield '  ';
        yield CoreExtension::callMacro($macros['_self'], 'macro_list', [(isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 33, $this->source);
        })()), (($context['selectedItem']) ?? (null))], 33, $context, $this->getSourceContext());
        yield '
';
        echo craft\helpers\Html::tag('nav', ob_get_clean(), ['aria' => ['label' => ((        // line 30
            $context['label']) ?? (false))]]);
        craft\helpers\Template::endProfile('template', '_includes/nav');
        yield from [];
    }

    // line 1
    public function macro_list($__items__ = null, $__selectedItem__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'items' => $__items__,
            'selectedItem' => $__selectedItem__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'list');
            // line 2
            yield '  <ul>
    ';
            // line 3
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
                throw new RuntimeError('Variable "items" does not exist.', 3, $this->source);
            })()));
            foreach ($context['_seq'] as $context['key'] => $context['item']) {
                // line 4
                yield '      ';
                if ($context['item']) {
                    // line 5
                    yield '        ';
                    ob_start();
                    // line 10
                    yield '          ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'heading', [], 'any', true, true, false, 10)) {
                        // line 11
                        yield '            <span>';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'heading', [], 'any', false, false, false, 11), 'html', null, true);
                        yield '</span>
            ';
                        // line 12
                        yield CoreExtension::callMacro($macros['_self'], 'macro_list', [craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'nested', [], 'any', false, false, false, 12), (isset($context['selectedItem']) || array_key_exists('selectedItem', $context) ? $context['selectedItem'] : (function () {
                            throw new RuntimeError('Variable "selectedItem" does not exist.', 12, $this->source);
                        })())], 12, $context, $this->getSourceContext());
                        yield '
          ';
                    } else {
                        // line 14
                        yield '            ';
                        yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['class' => Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['sel' => ((((craft\helpers\Template::attribute($this->env, $this->source,                         // line 16
                            $context['item'], 'selected', [], 'any', true, true, false, 16) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'selected', [], 'any', false, false, false, 16) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'selected', [], 'any', false, false, false, 16)) : (false)) || ((isset($context['selectedItem']) || array_key_exists('selectedItem', $context) ? $context['selectedItem'] : (function () {
                                throw new RuntimeError('Variable "selectedItem" does not exist.', 16, $this->source);
                            })()) && ($context['key'] == (isset($context['selectedItem']) || array_key_exists('selectedItem', $context) ? $context['selectedItem'] : (function () {
                                throw new RuntimeError('Variable "selectedItem" does not exist.', 16, $this->source);
                            })()))))])), 'href' => craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source,                         // line 18
                                $context['item'], 'url', [], 'any', false, false, false, 18)), 'text' => craft\helpers\Template::attribute($this->env, $this->source,                         // line 19
                                    $context['item'], 'label', [], 'any', false, false, false, 19)]);
                        // line 20
                        yield '
          ';
                    }
                    // line 22
                    yield '        ';
                    echo craft\helpers\Html::tag('li', ob_get_clean(), ['class' => Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['heading' => craft\helpers\Template::attribute($this->env, $this->source,                     // line 7
                        $context['item'], 'heading', [], 'any', true, true, false, 7)]))]);
                    // line 23
                    yield '      ';
                }
                // line 24
                yield '    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['key'], $context['item'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 25
            yield '  </ul>
';
            craft\helpers\Template::endProfile('macro', 'list');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/nav';
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
        return [123 => 25,  117 => 24,  114 => 23,  112 => 7,  110 => 22,  106 => 20,  104 => 19,  103 => 18,  102 => 16,  100 => 14,  95 => 12,  90 => 11,  87 => 10,  84 => 5,  81 => 4,  77 => 3,  74 => 2,  60 => 1,  54 => 30,  49 => 33,  47 => 28,  44 => 27];
    }

    public function getSourceContext(): Source
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
            {{ _self.list(item.nested, selectedItem) }}
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
", '_includes/nav', '/tmp/packages/craft5/src/templates/_includes/nav.twig');
    }
}
