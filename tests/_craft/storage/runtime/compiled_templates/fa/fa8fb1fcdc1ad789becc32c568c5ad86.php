<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _elements/footer */
class __TwigTemplate_41ddf77c06f8d6f359d7a716e82697fa extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_elements/footer');
        // line 1
        echo '<div id="count-spinner" class="spinner small hidden"></div>
<div id="count-container" class="light">&nbsp;</div>
<div id="actions-container" class="flex"></div>
<div class="flex flex-nowrap">
  <button type="button" id="export-btn" class="btn hidden" aria-expanded="false">';
        // line 5
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Export…', 'app'), 'html', null, true);
        echo '</button>
</div>
';
        craft\helpers\Template::endProfile('template', '_elements/footer');
    }

    public function getTemplateName()
    {
        return '_elements/footer';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [44 => 5,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div id=\"count-spinner\" class=\"spinner small hidden\"></div>
<div id=\"count-container\" class=\"light\">&nbsp;</div>
<div id=\"actions-container\" class=\"flex\"></div>
<div class=\"flex flex-nowrap\">
  <button type=\"button\" id=\"export-btn\" class=\"btn hidden\" aria-expanded=\"false\">{{ 'Export…'|t('app') }}</button>
</div>
", '_elements/footer', '/Users/brianhanson/Development/craft5/src/templates/_elements/footer.twig');
    }
}
