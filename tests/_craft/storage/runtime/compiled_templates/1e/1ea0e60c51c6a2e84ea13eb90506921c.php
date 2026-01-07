<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* entries */
class __TwigTemplate_46f7e34b296952c08ebd25a88303cca6 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return '_layouts/elementindex';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'entries');
        // line 2
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Entries', 'app');
        // line 3
        $context['elementType'] = 'craft\\elements\\Entry';
        // line 5
        if (array_key_exists('sectionHandle', $context)) {
            // line 6
            ob_start();
            // line 7
            yield '        window.defaultSectionHandle = "';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['sectionHandle']) || array_key_exists('sectionHandle', $context) ? $context['sectionHandle'] : (function () {
                throw new RuntimeError('Variable "sectionHandle" does not exist.', 7, $this->source);
            })()), 'js'), 'html', null, true);
            yield '";
    ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        // line 1
        $this->parent = $this->loadTemplate('_layouts/elementindex', 'entries', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'entries');
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'entries';
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
        return [62 => 1,  55 => 7,  53 => 6,  51 => 5,  49 => 3,  47 => 2,  39 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"_layouts/elementindex\" %}
{% set title = \"Entries\"|t('app') %}
{% set elementType = 'craft\\\\elements\\\\Entry' %}

{% if sectionHandle is defined %}
    {% js %}
        window.defaultSectionHandle = \"{{ sectionHandle|e('js') }}\";
    {% endjs %}
{% endif %}
", 'entries', '/tmp/packages/craft5/src/templates/entries/index.twig');
    }
}
