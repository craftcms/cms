<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/utilities/PhpInfo.twig */
class __TwigTemplate_5273c5459f9a55c0fb213885aafb20ea extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/PhpInfo.twig');
        // line 1
        echo '<div class="readable">
    ';
        // line 2
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['phpInfo']) || array_key_exists('phpInfo', $context) ? $context['phpInfo'] : (function () {
            throw new RuntimeError('Variable "phpInfo" does not exist.', 2, $this->source);
        })()));
        foreach ($context['_seq'] as $context['section'] => $context['values']) {
            // line 3
            echo '        <h2>';
            echo twig_escape_filter($this->env, $context['section'], 'html', null, true);
            echo '</h2>
        <table class="data fullwidth fixed-layout">
            <tbody>
                ';
            // line 6
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($context['values']);
            foreach ($context['_seq'] as $context['key'] => $context['value']) {
                // line 7
                echo '                    <tr>
                        <th class="light">';
                // line 8
                echo $context['key'];
                echo '</th>
                        <td>';
                // line 10
                if (twig_test_iterable($context['value'])) {
                    // line 11
                    echo twig_escape_filter($this->env, twig_join_filter($context['value'], ', '), 'html', null, true);
                } else {
                    // line 13
                    echo twig_escape_filter($this->env, $context['value'], 'html', null, true);
                }
                // line 15
                echo '</td>
                    </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['key'], $context['value'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 18
            echo '            </tbody>
        </table>
    ';
        }
        unset($context['_seq'], $context['_iterated'], $context['section'], $context['values'], $context['_parent'], $context['loop']);
        // line 21
        echo '</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/PhpInfo.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/PhpInfo.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [87 => 21,  79 => 18,  71 => 15,  68 => 13,  65 => 11,  63 => 10,  59 => 8,  56 => 7,  52 => 6,  45 => 3,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div class=\"readable\">
    {% for section, values in phpInfo %}
        <h2>{{ section }}</h2>
        <table class=\"data fullwidth fixed-layout\">
            <tbody>
                {% for key, value in values %}
                    <tr>
                        <th class=\"light\">{{ key|raw }}</th>
                        <td>
                            {%- if value is iterable %}
                                {{- value|join(', ') }}
                            {%- else %}
                                {{- value }}
                            {%- endif -%}
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    {% endfor %}
</div>
", '_components/utilities/PhpInfo.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/PhpInfo.twig');
    }
}
