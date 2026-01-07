<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/utilities/PhpInfo.twig */
class __TwigTemplate_678dbaec60ddb1fd5a0d585726a1b9d8 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/PhpInfo.twig');
        // line 1
        yield '<div class="readable">
    ';
        // line 2
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['phpInfo']) || array_key_exists('phpInfo', $context) ? $context['phpInfo'] : (function () {
            throw new RuntimeError('Variable "phpInfo" does not exist.', 2, $this->source);
        })()));
        foreach ($context['_seq'] as $context['section'] => $context['values']) {
            // line 3
            yield '        <h2>';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['section'], 'html', null, true);
            yield '</h2>
        <table class="data fullwidth fixed-layout">
            <tbody>
                ';
            // line 6
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable($context['values']);
            foreach ($context['_seq'] as $context['key'] => $context['value']) {
                // line 7
                yield '                    <tr>
                        <th class="light">';
                // line 8
                yield $context['key'];
                yield '</th>
                        <td>';
                // line 10
                if (is_iterable($context['value'])) {
                    // line 11
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(Twig\Extension\CoreExtension::join($context['value'], ', '), 'html', null, true);
                } else {
                    // line 13
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['value'], 'html', null, true);
                }
                // line 15
                yield '</td>
                    </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['key'], $context['value'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 18
            yield '            </tbody>
        </table>
    ';
        }
        unset($context['_seq'], $context['section'], $context['values'], $context['_parent']);
        // line 21
        yield '</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/PhpInfo.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/PhpInfo.twig';
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
        return [92 => 21,  84 => 18,  76 => 15,  73 => 13,  70 => 11,  68 => 10,  64 => 8,  61 => 7,  57 => 6,  50 => 3,  46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
", '_components/utilities/PhpInfo.twig', '/tmp/packages/craft5/src/templates/_components/utilities/PhpInfo.twig');
    }
}
