<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__b964bbcf6b20d4fd55080283dd4ceb10 */
class __TwigTemplate_a4a763e968cc61676dddefc17f873554 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b964bbcf6b20d4fd55080283dd4ceb10');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->timeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'h:i:s');
        craft\helpers\Template::endProfile('template', '__string_template__b964bbcf6b20d4fd55080283dd4ceb10');
    }

    public function getTemplateName()
    {
        return '__string_template__b964bbcf6b20d4fd55080283dd4ceb10';
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
        return new Source('{{ d|time("h:i:s") }}', '__string_template__b964bbcf6b20d4fd55080283dd4ceb10', '');
    }
}
