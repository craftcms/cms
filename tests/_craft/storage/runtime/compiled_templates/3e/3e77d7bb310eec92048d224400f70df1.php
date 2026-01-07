<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/widgets/RecentEntries/body.twig */
class __TwigTemplate_b7b065b579cef3f7e590c435d4107c8e extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/widgets/RecentEntries/body.twig');
        // line 1
        echo '<div class="recententries-container">
    ';
        // line 2
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['entries']) || array_key_exists('entries', $context) ? $context['entries'] : (function () {
            throw new RuntimeError('Variable "entries" does not exist.', 2, $this->source);
        })()))) {
            // line 3
            echo '        <ol class="widget__list" role="list">
            ';
            // line 4
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['entries']) || array_key_exists('entries', $context) ? $context['entries'] : (function () {
                throw new RuntimeError('Variable "entries" does not exist.', 4, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['entry']) {
                // line 5
                echo '                <li class="widget__list-item">
                    <a href="';
                // line 6
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'getCpEditUrl', [], 'method'), 'html', null, true);
                echo '">';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'title', []), 'html', null, true);
                echo '</a>
                    <span class="light">
                        ';
                // line 8
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->timestampFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'dateCreated', []), 'short'), 'html', null, true);
                // line 9
                if ((((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
                    throw new RuntimeError('Variable "CraftEdition" does not exist.', 9, $this->source);
                })()) == (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
                    throw new RuntimeError('Variable "CraftPro" does not exist.', 9, $this->source);
                })())) && craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'getAuthor', [], 'method'))) {
                    echo ', ';
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'getAuthor', [], 'method'), 'username', []), 'html', null, true);
                }
                // line 10
                echo '</span>
                </li>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['entry'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 13
            echo '        </ol>
    ';
        } else {
            // line 15
            echo '        <p class="zilch small">';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No entries exist yet.', 'app'), 'html', null, true);
            echo '</p>
    ';
        }
        // line 17
        echo '</div>
';
        craft\helpers\Template::endProfile('template', '_components/widgets/RecentEntries/body.twig');
    }

    public function getTemplateName()
    {
        return '_components/widgets/RecentEntries/body.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [85 => 17,  79 => 15,  75 => 13,  67 => 10,  62 => 9,  60 => 8,  53 => 6,  50 => 5,  46 => 4,  43 => 3,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div class=\"recententries-container\">
    {% if entries|length %}
        <ol class=\"widget__list\" role=\"list\">
            {% for entry in entries %}
                <li class=\"widget__list-item\">
                    <a href=\"{{ entry.getCpEditUrl() }}\">{{ entry.title }}</a>
                    <span class=\"light\">
                        {{ entry.dateCreated|timestamp('short') }}
                        {%- if CraftEdition == CraftPro and entry.getAuthor() %}, {{ entry.getAuthor().username }}{% endif -%}
                    </span>
                </li>
            {% endfor %}
        </ol>
    {% else %}
        <p class=\"zilch small\">{{ \"No entries exist yet.\"|t('app') }}</p>
    {% endif %}
</div>
", '_components/widgets/RecentEntries/body.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/widgets/RecentEntries/body.twig');
    }
}
