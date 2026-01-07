<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/general/_images/icon.twig */
class __TwigTemplate_4ce2788a1a2a2abf060b1db724ba4590 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'changeIconLabel' => $this->block_changeIconLabel(...),
            'deleteIconLabel' => $this->block_deleteIconLabel(...),
            'uploadIconLabel' => $this->block_uploadIconLabel(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return 'settings/general/_images/image';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'settings/general/_images/icon.twig');
        // line 3
        $context['imageType'] = 'icon';
        // line 1
        $this->parent = $this->loadTemplate('settings/general/_images/image', 'settings/general/_images/icon.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/general/_images/icon.twig');
    }

    // line 5
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_changeIconLabel(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'changeIconLabel');
        // line 6
        yield '    ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Change icon', 'app'), 'html', null, true);
        yield '
';
        craft\helpers\Template::endProfile('block', 'changeIconLabel');
        yield from [];
    }

    // line 9
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_deleteIconLabel(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'deleteIconLabel');
        // line 10
        yield '    ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Delete icon', 'app'), 'html', null, true);
        yield '
';
        craft\helpers\Template::endProfile('block', 'deleteIconLabel');
        yield from [];
    }

    // line 13
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_uploadIconLabel(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'uploadIconLabel');
        // line 14
        yield '    ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Upload icon', 'app'), 'html', null, true);
        yield '
';
        craft\helpers\Template::endProfile('block', 'uploadIconLabel');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/general/_images/icon.twig';
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
        return [100 => 14,  92 => 13,  83 => 10,  75 => 9,  66 => 6,  58 => 5,  52 => 1,  50 => 3,  42 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"settings/general/_images/image\" %}

{% set imageType = \"icon\" %}

{% block changeIconLabel %}
    {{ 'Change icon'|t('app') }}
{% endblock %}

{% block deleteIconLabel %}
    {{ 'Delete icon'|t('app') }}
{% endblock %}

{% block uploadIconLabel %}
    {{ 'Upload icon'|t('app') }}
{% endblock %}
", 'settings/general/_images/icon.twig', '/tmp/packages/craft5/src/templates/settings/general/_images/icon.twig');
    }
}
