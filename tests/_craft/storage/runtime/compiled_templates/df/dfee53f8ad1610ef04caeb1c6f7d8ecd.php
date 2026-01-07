<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__3e9129477d62d306addd12c75e8282c7 */
class __TwigTemplate_ab4dae05e5c2aa7a8aef870f31e2b805 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__3e9129477d62d306addd12c75e8282c7');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->dateFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'php:Y-m-d');
        craft\helpers\Template::endProfile('template', '__string_template__3e9129477d62d306addd12c75e8282c7');
    }

    public function getTemplateName()
    {
        return '__string_template__3e9129477d62d306addd12c75e8282c7';
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
        return new Source('{{ d|date("php:Y-m-d") }}', '__string_template__3e9129477d62d306addd12c75e8282c7', '');
    }
}
