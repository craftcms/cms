<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/header-photo.twig */
class __TwigTemplate_d8a77bc31ebb12a3473448d4ff87b9ba extends Template
{
    private $source;

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
        craft\helpers\Template::beginProfile('template', '_layouts/components/header-photo.twig');
        // line 1
        echo '<div class="header-photo">
  ';
        // line 2
        echo craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 2, $this->source);
        })()), 'getThumbHtml', [0 => 30], 'method');
        echo '
</div>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/header-photo.twig');
    }

    public function getTemplateName()
    {
        return '_layouts/components/header-photo.twig';
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
        return new Source('<div class="header-photo">
  {{ currentUser.getThumbHtml(30)|raw }}
</div>
', '_layouts/components/header-photo.twig', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/header-photo.twig');
    }
}
