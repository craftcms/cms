<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__37d2b74c929304b6aadbbe84f440130a */
class __TwigTemplate_7c46335170dcd8bad3d0a37a4b901594 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__37d2b74c929304b6aadbbe84f440130a');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->dateFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'icu:YYYY-MM-dd');
        craft\helpers\Template::endProfile('template', '__string_template__37d2b74c929304b6aadbbe84f440130a');
    }

    public function getTemplateName()
    {
        return '__string_template__37d2b74c929304b6aadbbe84f440130a';
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
        return new Source('{{ d|date("icu:YYYY-MM-dd") }}', '__string_template__37d2b74c929304b6aadbbe84f440130a', '');
    }
}
