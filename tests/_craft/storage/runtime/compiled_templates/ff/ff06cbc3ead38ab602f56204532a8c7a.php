<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/Updates.twig */
class __TwigTemplate_c784f4b56c4b80d0ac72032ca35d6072 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/Updates.twig');
        // line 1
        echo '<div id="graphic" class="spinner big"></div>
<div id="status">';
        // line 2
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Checking for updates…', 'app'), 'html', null, true);
        echo '</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/Updates.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/Updates.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div id=\"graphic\" class=\"spinner big\"></div>
<div id=\"status\">{{ \"Checking for updates…\"|t('app') }}</div>
", '_components/utilities/Updates.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/Updates.twig');
    }
}
