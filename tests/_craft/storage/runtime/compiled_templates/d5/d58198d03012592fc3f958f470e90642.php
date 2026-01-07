<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/utilities/ClearCaches.twig */
class __TwigTemplate_d0eca488f6544648bf1f2a3452cf5f5b extends Template
{
    private readonly Source $source;

    /**
     * @var array<string, Template>
     */
    private array $macros = [];

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
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/utilities/ClearCaches.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/ClearCaches.twig', 1)->unwrap();
        // line 2
        yield '
<h2>';
        // line 3
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Clear Caches', 'app'), 'html', null, true);
        yield '</h2>

<form class="utility" method="post" accept-charset="UTF-8">
    ';
        // line 6
        yield craft\helpers\Html::actionInput('utilities/clear-caches-perform-action');
        yield '
    ';
        // line 7
        yield craft\helpers\Html::csrfInput();
        yield '

    ';
        // line 9
        yield CoreExtension::callMacro($macros['forms'], 'macro_checkboxSelect', [['name' => 'caches', 'options' =>         // line 11
(isset($context['cacheOptions']) || array_key_exists('cacheOptions', $context) ? $context['cacheOptions'] : (function () {
    throw new RuntimeError('Variable "cacheOptions" does not exist.', 11, $this->source);
})()), 'showAllOption' => true, 'values' => '*']], 9, $context, $this->getSourceContext());
        // line 14
        yield '

    <div class="buttons">
        <button type="submit" class="btn submit secondary">';
        // line 17
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Clear caches', 'app'), 'html', null, true);
        yield '</button>
        <div class="utility-status"></div>
    </div>
</form>

<hr>

<h2>';
        // line 24
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Invalidate Data Caches', 'app'), 'html', null, true);
        yield '</h2>

<form class="utility" method="post" accept-charset="UTF-8">
    ';
        // line 27
        yield craft\helpers\Html::actionInput('utilities/invalidate-tags');
        yield '
    ';
        // line 28
        yield craft\helpers\Html::csrfInput();
        yield '

    ';
        // line 30
        yield CoreExtension::callMacro($macros['forms'], 'macro_checkboxSelect', [['name' => 'tags', 'options' =>         // line 32
(isset($context['tagOptions']) || array_key_exists('tagOptions', $context) ? $context['tagOptions'] : (function () {
    throw new RuntimeError('Variable "tagOptions" does not exist.', 32, $this->source);
})()), 'showAllOption' => false]], 30, $context, $this->getSourceContext());
        // line 34
        yield '

    <div class="buttons">
        <button type="submit" class="btn submit secondary">';
        // line 37
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Invalidate caches', 'app'), 'html', null, true);
        yield '</button>
        <div class="utility-status"></div>
    </div>
</form>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/ClearCaches.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/ClearCaches.twig';
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
        return [104 => 37,  99 => 34,  97 => 32,  96 => 30,  91 => 28,  87 => 27,  81 => 24,  71 => 17,  66 => 14,  64 => 11,  63 => 9,  58 => 7,  54 => 6,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/forms\" as forms %}

<h2>{{ 'Clear Caches'|t('app') }}</h2>

<form class=\"utility\" method=\"post\" accept-charset=\"UTF-8\">
    {{ actionInput('utilities/clear-caches-perform-action') }}
    {{ csrfInput() }}

    {{ forms.checkboxSelect({
        name: 'caches',
        options: cacheOptions,
        showAllOption: true,
        values: '*',
    }) }}

    <div class=\"buttons\">
        <button type=\"submit\" class=\"btn submit secondary\">{{ 'Clear caches'|t('app') }}</button>
        <div class=\"utility-status\"></div>
    </div>
</form>

<hr>

<h2>{{ 'Invalidate Data Caches'|t('app') }}</h2>

<form class=\"utility\" method=\"post\" accept-charset=\"UTF-8\">
    {{ actionInput('utilities/invalidate-tags') }}
    {{ csrfInput() }}

    {{ forms.checkboxSelect({
        name: 'tags',
        options: tagOptions,
        showAllOption: false,
    }) }}

    <div class=\"buttons\">
        <button type=\"submit\" class=\"btn submit secondary\">{{ 'Invalidate caches'|t('app') }}</button>
        <div class=\"utility-status\"></div>
    </div>
</form>
", '_components/utilities/ClearCaches.twig', '/tmp/packages/craft5/src/templates/_components/utilities/ClearCaches.twig');
    }
}
