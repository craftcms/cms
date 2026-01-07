<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/utilities/ClearCaches.twig */
class __TwigTemplate_a48dcb6fbdcd70c5be7f21edaa699570 extends Template
{
    private $source;

    private $macros = [];

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
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/utilities/ClearCaches.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/ClearCaches.twig', 1)->unwrap();
        // line 2
        echo '
<h2>';
        // line 3
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Clear Caches', 'app'), 'html', null, true);
        echo '</h2>

<form class="utility" method="post" accept-charset="UTF-8">
    ';
        // line 6
        echo craft\helpers\Html::actionInput('utilities/clear-caches-perform-action');
        echo '
    ';
        // line 7
        echo craft\helpers\Html::csrfInput();
        echo '

    ';
        // line 9
        echo twig_call_macro($macros['forms'], 'macro_checkboxSelect', [['name' => 'caches', 'options' =>         // line 11
(isset($context['cacheOptions']) || array_key_exists('cacheOptions', $context) ? $context['cacheOptions'] : (function () {
    throw new RuntimeError('Variable "cacheOptions" does not exist.', 11, $this->source);
})()), 'showAllOption' => true, 'values' => '*', ]], 9, $context, $this->getSourceContext());
        // line 14
        echo '

    <div class="buttons">
        <button type="submit" class="btn submit secondary">';
        // line 17
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Clear caches', 'app'), 'html', null, true);
        echo '</button>
        <div class="utility-status"></div>
    </div>
</form>

<hr>

<h2>';
        // line 24
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Invalidate Data Caches', 'app'), 'html', null, true);
        echo '</h2>

<form class="utility" method="post" accept-charset="UTF-8">
    ';
        // line 27
        echo craft\helpers\Html::actionInput('utilities/invalidate-tags');
        echo '
    ';
        // line 28
        echo craft\helpers\Html::csrfInput();
        echo '

    ';
        // line 30
        echo twig_call_macro($macros['forms'], 'macro_checkboxSelect', [['name' => 'tags', 'options' =>         // line 32
(isset($context['tagOptions']) || array_key_exists('tagOptions', $context) ? $context['tagOptions'] : (function () {
    throw new RuntimeError('Variable "tagOptions" does not exist.', 32, $this->source);
})()), 'showAllOption' => false, ]], 30, $context, $this->getSourceContext());
        // line 34
        echo '

    <div class="buttons">
        <button type="submit" class="btn submit secondary">';
        // line 37
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Invalidate caches', 'app'), 'html', null, true);
        echo '</button>
        <div class="utility-status"></div>
    </div>
</form>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/ClearCaches.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/ClearCaches.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [99 => 37,  94 => 34,  92 => 32,  91 => 30,  86 => 28,  82 => 27,  76 => 24,  66 => 17,  61 => 14,  59 => 11,  58 => 9,  53 => 7,  49 => 6,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
", '_components/utilities/ClearCaches.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/ClearCaches.twig');
    }
}
