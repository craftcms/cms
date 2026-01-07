<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/general/_images/logo.twig */
class __TwigTemplate_c3b2b9f0981270ad2a65f757aef0d192 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'changeLogoLabel' => $this->block_changeLogoLabel(...),
            'deleteLogoLabel' => $this->block_deleteLogoLabel(...),
            'uploadLogoLabel' => $this->block_uploadLogoLabel(...),
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
        craft\helpers\Template::beginProfile('template', 'settings/general/_images/logo.twig');
        // line 3
        $context['imageType'] = 'logo';
        // line 1
        $this->parent = $this->loadTemplate('settings/general/_images/image', 'settings/general/_images/logo.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/general/_images/logo.twig');
    }

    // line 5
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_changeLogoLabel(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'changeLogoLabel');
        // line 6
        yield '    ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Change logo', 'app'), 'html', null, true);
        yield '
';
        craft\helpers\Template::endProfile('block', 'changeLogoLabel');
        yield from [];
    }

    // line 9
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_deleteLogoLabel(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'deleteLogoLabel');
        // line 10
        yield '    ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Delete logo', 'app'), 'html', null, true);
        yield '
';
        craft\helpers\Template::endProfile('block', 'deleteLogoLabel');
        yield from [];
    }

    // line 13
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_uploadLogoLabel(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'uploadLogoLabel');
        // line 14
        yield '    ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Upload logo', 'app'), 'html', null, true);
        yield '
';
        craft\helpers\Template::endProfile('block', 'uploadLogoLabel');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/general/_images/logo.twig';
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

{% set imageType = \"logo\" %}

{% block changeLogoLabel %}
    {{ 'Change logo'|t('app') }}
{% endblock %}

{% block deleteLogoLabel %}
    {{ 'Delete logo'|t('app') }}
{% endblock %}

{% block uploadLogoLabel %}
    {{ 'Upload logo'|t('app') }}
{% endblock %}
", 'settings/general/_images/logo.twig', '/tmp/packages/craft5/src/templates/settings/general/_images/logo.twig');
    }
}
