<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/widgets/RecentEntries/body.twig */
class __TwigTemplate_7b40d51c657c1a4cdb0f5fcb9652f67a extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/widgets/RecentEntries/body.twig');
        // line 1
        yield '<div class="recententries-container">
    ';
        // line 2
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['entries']) || array_key_exists('entries', $context) ? $context['entries'] : (function () {
            throw new RuntimeError('Variable "entries" does not exist.', 2, $this->source);
        })()))) {
            // line 3
            yield '        <ol class="widget__list" role="list">
            ';
            // line 4
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['entries']) || array_key_exists('entries', $context) ? $context['entries'] : (function () {
                throw new RuntimeError('Variable "entries" does not exist.', 4, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['entry']) {
                // line 5
                yield '                <li class="widget__list-item">
                    <a href="';
                // line 6
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'getCpEditUrl', [], 'method', false, false, false, 6), 'html', null, true);
                yield '">';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'title', [], 'any', false, false, false, 6), 'html', null, true);
                yield '</a>
                    <span class="light">
                        ';
                // line 8
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->timestampFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'dateCreated', [], 'any', false, false, false, 8), 'short'), 'html', null, true);
                // line 9
                if ((((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
                    throw new RuntimeError('Variable "CraftEdition" does not exist.', 9, $this->source);
                })()) != (isset($context['CraftSolo']) || array_key_exists('CraftSolo', $context) ? $context['CraftSolo'] : (function () {
                    throw new RuntimeError('Variable "CraftSolo" does not exist.', 9, $this->source);
                })())) && craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'getAuthor', [], 'method', false, false, false, 9))) {
                    yield ', ';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['entry'], 'getAuthor', [], 'method', false, false, false, 9), 'username', [], 'any', false, false, false, 9), 'html', null, true);
                }
                // line 10
                yield '</span>
                </li>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['entry'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 13
            yield '        </ol>
    ';
        } else {
            // line 15
            yield '        <p class="zilch small">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('No entries exist yet.', 'app'), 'html', null, true);
            yield '</p>
    ';
        }
        // line 17
        yield '</div>
';
        craft\helpers\Template::endProfile('template', '_components/widgets/RecentEntries/body.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/widgets/RecentEntries/body.twig';
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
        return [90 => 17,  84 => 15,  80 => 13,  72 => 10,  67 => 9,  65 => 8,  58 => 6,  55 => 5,  51 => 4,  48 => 3,  46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("<div class=\"recententries-container\">
    {% if entries|length %}
        <ol class=\"widget__list\" role=\"list\">
            {% for entry in entries %}
                <li class=\"widget__list-item\">
                    <a href=\"{{ entry.getCpEditUrl() }}\">{{ entry.title }}</a>
                    <span class=\"light\">
                        {{ entry.dateCreated|timestamp('short') }}
                        {%- if CraftEdition != CraftSolo and entry.getAuthor() %}, {{ entry.getAuthor().username }}{% endif -%}
                    </span>
                </li>
            {% endfor %}
        </ol>
    {% else %}
        <p class=\"zilch small\">{{ \"No entries exist yet.\"|t('app') }}</p>
    {% endif %}
</div>
", '_components/widgets/RecentEntries/body.twig', '/tmp/packages/craft5/src/templates/_components/widgets/RecentEntries/body.twig');
    }
}
