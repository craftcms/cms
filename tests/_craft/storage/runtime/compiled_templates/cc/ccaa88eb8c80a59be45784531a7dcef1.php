<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__0f24a554b52febe314d4c6179c0dce9b */
class __TwigTemplate_b3e69387add1ca6b1979a81552ec3c03 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0f24a554b52febe314d4c6179c0dce9b');
        // line 1
        echo craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 1, $this->source);
        })()), 'firstName', []);
        echo ' | ';
        echo craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 1, $this->source);
        })()), 'lastName', []);
        craft\helpers\Template::endProfile('template', '__string_template__0f24a554b52febe314d4c6179c0dce9b');
    }

    public function getTemplateName()
    {
        return '__string_template__0f24a554b52febe314d4c6179c0dce9b';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('{{ currentUser.firstName }} | {{ currentUser.lastName }}', '__string_template__0f24a554b52febe314d4c6179c0dce9b', '');
    }
}
