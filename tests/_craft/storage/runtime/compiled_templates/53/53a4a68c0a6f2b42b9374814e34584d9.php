<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/widgets/Feed/body.twig */
class __TwigTemplate_d83b58860d790b399dd372b5a15481a1 extends Template
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
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/widgets/Feed/body.twig');
        // line 1
        $macros['links'] = $this->macros['links'] = $this->loadTemplate('_includes/links', '_components/widgets/Feed/body.twig', 1)->unwrap();
        // line 2
        echo '
<ol class="widget__list" role="list" dir="';
        // line 3
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['feed']) || array_key_exists('feed', $context) ? $context['feed'] : (function () {
            throw new RuntimeError('Variable "feed" does not exist.', 3, $this->source);
        })()), 'direction', []), 'html', null, true);
        echo '">
    ';
        // line 4
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['feed']) || array_key_exists('feed', $context) ? $context['feed'] : (function () {
            throw new RuntimeError('Variable "feed" does not exist.', 4, $this->source);
        })()), 'items', []));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 5
            echo '        <li class="widget__list-item">
            ';
            // line 6
            if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'permalink', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'permalink', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'permalink', [])) : (false))) {
                // line 7
                echo '                ';
                echo twig_call_macro($macros['links'], 'macro_externalLink', [['link' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 8
                    $context['item'], 'permalink', []), 'text' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 9
                        $context['item'], 'title', [])]], 7, $context, $this->getSourceContext());
                // line 10
                echo '
                ';
                // line 11
                if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'date', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'date', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'date', [])) : (false))) {
                    // line 12
                    echo '                    ';
                    echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'light nowrap', 'text' => $this->extensions['craft\web\twig\Extension']->timestampFilter(craft\helpers\Template::attribute($this->env, $this->source,                     // line 14
                        $context['item'], 'date', []), 'short')]);
                    // line 15
                    echo '
                ';
                }
                // line 17
                echo '            ';
            } else {
                // line 18
                echo '                &nbsp;
            ';
            }
            // line 20
            echo '        </li>
    ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
        // line 22
        echo '</ol>
';
        craft\helpers\Template::endProfile('template', '_components/widgets/Feed/body.twig');
    }

    public function getTemplateName()
    {
        return '_components/widgets/Feed/body.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [88 => 22,  81 => 20,  77 => 18,  74 => 17,  70 => 15,  68 => 14,  66 => 12,  64 => 11,  61 => 10,  59 => 9,  58 => 8,  56 => 7,  54 => 6,  51 => 5,  47 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import \"_includes/links\" as links %}

<ol class=\"widget__list\" role=\"list\" dir=\"{{ feed.direction }}\">
    {% for item in feed.items %}
        <li class=\"widget__list-item\">
            {% if item.permalink ?? false %}
                {{ links.externalLink({
                    link: item.permalink,
                    text: item.title,
                }) }}
                {% if item.date ?? false %}
                    {{ tag('span', {
                        class: 'light nowrap',
                        text: item.date|timestamp('short'),
                    }) }}
                {% endif %}
            {% else %}
                &nbsp;
            {% endif %}
        </li>
    {% endfor %}
</ol>
", '_components/widgets/Feed/body.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/widgets/Feed/body.twig');
    }
}
