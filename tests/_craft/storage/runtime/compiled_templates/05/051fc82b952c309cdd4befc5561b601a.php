<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* utilities/_index.twig */
class __TwigTemplate_d3607791326715d9f040b29f5650de63 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'sidebar' => $this->block_sidebar(...),
            'toolbar' => $this->block_toolbar(...),
            'content' => $this->block_content(...),
            'footer' => $this->block_footer(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'utilities/_index.twig');
        // line 3
        $context['title'] = (isset($context['displayName']) || array_key_exists('displayName', $context) ? $context['displayName'] : (function () {
            throw new RuntimeError('Variable "displayName" does not exist.', 3, $this->source);
        })());
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'utilities/_index.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'utilities/_index.twig');
    }

    // line 5
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_sidebar(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 6
        yield '    <nav aria-label="';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Utilities', 'app'), 'html', null, true);
        yield '">
        <ul>
            ';
        // line 8
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['utilities']) || array_key_exists('utilities', $context) ? $context['utilities'] : (function () {
            throw new RuntimeError('Variable "utilities" does not exist.', 8, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['utility']) {
            // line 9
            yield '                ';
            $context['selected'] = (craft\helpers\Template::attribute($this->env, $this->source, $context['utility'], 'id', [], 'any', false, false, false, 9) == (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 9, $this->source);
            })()));
            // line 10
            yield '                <li>
                    <a class="';
            // line 11
            if ((isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                throw new RuntimeError('Variable "selected" does not exist.', 11, $this->source);
            })())) {
                yield 'sel';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source, $context['utility'], 'badgeCount', [], 'any', false, false, false, 11)) {
                yield 'has-badge';
            }
            yield '" href="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url(('utilities/'.craft\helpers\Template::attribute($this->env, $this->source, $context['utility'], 'id', [], 'any', false, false, false, 11))), 'html', null, true);
            yield '">
                        <span class="icon icon-mask" aria-hidden="true">';
            // line 12
            yield $this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, $context['utility'], 'iconSvg', [], 'any', false, false, false, 12), false);
            yield '</span>
                        <span class="label">';
            // line 13
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['utility'], 'displayName', [], 'any', false, false, false, 13), 'html', null, true);
            yield '</span>
                        ';
            // line 14
            if ((! (isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                throw new RuntimeError('Variable "selected" does not exist.', 14, $this->source);
            })()) && craft\helpers\Template::attribute($this->env, $this->source, $context['utility'], 'badgeCount', [], 'any', false, false, false, 14))) {
                // line 15
                yield '                            <span class="badge" aria-hidden="true">';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->numberFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['utility'], 'badgeCount', [], 'any', false, false, false, 15), 0), 'html', null, true);
                yield '</span>
                            ';
                // line 16
                yield $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'data' => ['notification' => true], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('{num, number} {num, plural, =1{notification} other{notifications}}', 'app', ['num' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 22
                    $context['utility'], 'badgeCount', [], 'any', false, false, false, 22)])]);
                // line 24
                yield '
                        ';
            }
            // line 26
            yield '                    </a>
                </li>
            ';
        }
        unset($context['_seq'], $context['_key'], $context['utility'], $context['_parent']);
        // line 29
        yield '        </ul>
    </nav>
';
        craft\helpers\Template::endProfile('block', 'sidebar');
        yield from [];
    }

    // line 33
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_toolbar(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'toolbar');
        // line 34
        yield '    ';
        yield isset($context['toolbarHtml']) || array_key_exists('toolbarHtml', $context) ? $context['toolbarHtml'] : (function () {
            throw new RuntimeError('Variable "toolbarHtml" does not exist.', 34, $this->source);
        })();
        yield '
';
        craft\helpers\Template::endProfile('block', 'toolbar');
        yield from [];
    }

    // line 37
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 38
        yield '    ';
        yield isset($context['contentHtml']) || array_key_exists('contentHtml', $context) ? $context['contentHtml'] : (function () {
            throw new RuntimeError('Variable "contentHtml" does not exist.', 38, $this->source);
        })();
        yield '
';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    // line 41
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_footer(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'footer');
        // line 42
        yield '    ';
        yield isset($context['footerHtml']) || array_key_exists('footerHtml', $context) ? $context['footerHtml'] : (function () {
            throw new RuntimeError('Variable "footerHtml" does not exist.', 42, $this->source);
        })();
        yield '
';
        craft\helpers\Template::endProfile('block', 'footer');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'utilities/_index.twig';
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
        return [173 => 42,  165 => 41,  156 => 38,  148 => 37,  139 => 34,  131 => 33,  123 => 29,  115 => 26,  111 => 24,  109 => 22,  108 => 16,  103 => 15,  101 => 14,  97 => 13,  93 => 12,  83 => 11,  80 => 10,  77 => 9,  73 => 8,  67 => 6,  59 => 5,  53 => 1,  51 => 3,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"_layouts/cp\" %}

{% set title = displayName %}

{% block sidebar %}
    <nav aria-label=\"{{ 'Utilities'|t('app') }}\">
        <ul>
            {% for utility in utilities %}
                {% set selected = utility.id == id %}
                <li>
                    <a class=\"{% if selected %}sel{% elseif utility.badgeCount %}has-badge{% endif %}\" href=\"{{ url('utilities/'~utility.id) }}\">
                        <span class=\"icon icon-mask\" aria-hidden=\"true\">{{ svg(utility.iconSvg, sanitize=false) }}</span>
                        <span class=\"label\">{{ utility.displayName }}</span>
                        {% if not selected and utility.badgeCount %}
                            <span class=\"badge\" aria-hidden=\"true\">{{ utility.badgeCount|number(decimals=0) }}</span>
                            {{ tag('span', {
                                class: 'visually-hidden',
                                data: {
                                    notification: true,
                                },
                                text: '{num, number} {num, plural, =1{notification} other{notifications}}'|t('app', {
                                   num: utility.badgeCount,
                                }),
                            }) }}
                        {% endif %}
                    </a>
                </li>
            {% endfor %}
        </ul>
    </nav>
{% endblock %}

{% block toolbar %}
    {{ toolbarHtml|raw }}
{% endblock %}

{% block content %}
    {{ contentHtml|raw }}
{% endblock %}

{% block footer %}
    {{ footerHtml|raw }}
{% endblock %}
", 'utilities/_index.twig', '/tmp/packages/craft5/src/templates/utilities/_index.twig');
    }
}
