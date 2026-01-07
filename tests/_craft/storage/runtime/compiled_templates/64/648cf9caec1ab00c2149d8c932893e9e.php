<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/system-info */
class __TwigTemplate_f3a4d0ba811fb33dfe9f71d369179ad6 extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/system-info');
        // line 1
        ob_start();
        // line 8
        yield '    <div id="site-icon">
        ';
        // line 9
        if ((isset($context['hasSystemIcon']) || array_key_exists('hasSystemIcon', $context) ? $context['hasSystemIcon'] : (function () {
            throw new RuntimeError('Variable "hasSystemIcon" does not exist.', 9, $this->source);
        })())) {
            // line 10
            yield '            <img src="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 10, $this->source);
            })()), 'rebrand', [], 'any', false, false, false, 10), 'icon', [], 'any', false, false, false, 10), 'url', [], 'any', false, false, false, 10), 'html', null, true);
            yield '" alt="">
        ';
        } else {
            // line 12
            yield '            ';
            yield craft\helpers\Cp::iconSvg('c-outline');
            yield '
        ';
        }
        // line 14
        yield '    </div>
    <div id="system-name">
        <span class="h2">';
        // line 16
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 16, $this->source);
        })()), 'html', null, true);
        yield '</span>
    </div>
';
        echo craft\helpers\Html::tag(((        // line 1
            (isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
                throw new RuntimeError('Variable "siteUrl" does not exist.', 1, $this->source);
            })())) ? ('a') : ('div')), ob_get_clean(), ['id' => 'system-info', 'href' => ((        // line 3
                $context['siteUrl']) ?? (false)), 'rel' => ((        // line 4
                    (isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
                        throw new RuntimeError('Variable "siteUrl" does not exist.', 4, $this->source);
                    })())) ? ('noopener') : (false)), 'target' => ((        // line 5
                        (isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
                            throw new RuntimeError('Variable "siteUrl" does not exist.', 5, $this->source);
                        })())) ? ('_blank') : (false)), 'title' => ((        // line 6
                            (isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
                                throw new RuntimeError('Variable "siteUrl" does not exist.', 6, $this->source);
                            })())) ? ($this->extensions['craft\web\twig\Extension']->translateFilter('View site', 'app')) : (false))]);
        craft\helpers\Template::endProfile('template', '_layouts/components/system-info');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/system-info';
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
        return [75 => 6,  74 => 5,  73 => 4,  72 => 3,  71 => 1,  66 => 16,  62 => 14,  56 => 12,  50 => 10,  48 => 9,  45 => 8,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% tag siteUrl ? 'a' : 'div' with {
    id: 'system-info',
    href: siteUrl ?? false,
    rel: siteUrl ? 'noopener' : false,
    target: siteUrl ? '_blank' : false,
    title: siteUrl ? 'View site'|t('app') : false,
} %}
    <div id=\"site-icon\">
        {% if hasSystemIcon %}
            <img src=\"{{ craft.rebrand.icon.url }}\" alt=\"\">
        {% else %}
            {{ iconSvg('c-outline') }}
        {% endif %}
    </div>
    <div id=\"system-name\">
        <span class=\"h2\">{{ systemName }}</span>
    </div>
{% endtag %}
", '_layouts/components/system-info', '/tmp/packages/craft5/src/templates/_layouts/components/system-info.twig');
    }
}
