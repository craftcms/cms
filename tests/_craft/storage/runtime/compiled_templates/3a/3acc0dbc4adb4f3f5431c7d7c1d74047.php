<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* entries */
class __TwigTemplate_8d0bddf8282ac0fe2440c04cff38b09b extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 1
        return '_layouts/elementindex';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'entries');
        // line 2
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Entries', 'app');
        // line 3
        $context['elementType'] = 'craft\\elements\\Entry';
        // line 5
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 5, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'New entry']], 'method');
        // line 9
        if (array_key_exists('sectionHandle', $context)) {
            // line 10
            ob_start();
            // line 11
            echo '        window.defaultSectionHandle = "';
            echo twig_escape_filter($this->env, twig_escape_filter($this->env, (isset($context['sectionHandle']) || array_key_exists('sectionHandle', $context) ? $context['sectionHandle'] : (function () {
                throw new RuntimeError('Variable "sectionHandle" does not exist.', 11, $this->source);
            })()), 'js'), 'html', null, true);
            echo '";
    ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        // line 1
        $this->parent = $this->loadTemplate('_layouts/elementindex', 'entries', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'entries');
    }

    public function getTemplateName()
    {
        return 'entries';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [59 => 1,  52 => 11,  50 => 10,  48 => 9,  46 => 5,  44 => 3,  42 => 2,  34 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"_layouts/elementindex\" %}
{% set title = \"Entries\"|t('app') %}
{% set elementType = 'craft\\\\elements\\\\Entry' %}

{% do view.registerTranslations('app', [
    'New entry',
]) %}

{% if sectionHandle is defined %}
    {% js %}
        window.defaultSectionHandle = \"{{ sectionHandle|e('js') }}\";
    {% endjs %}
{% endif %}
", 'entries', '/Users/brianhanson/Development/craft5/src/templates/entries/index.twig');
    }
}
