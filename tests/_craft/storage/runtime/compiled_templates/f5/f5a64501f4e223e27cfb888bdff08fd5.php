<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__791380e632e8eac22b4526bb39f0e0d5 */
class __TwigTemplate_4d7a3b31bbf348a257af45c0d380e55e extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__791380e632e8eac22b4526bb39f0e0d5');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->indexOfFilter((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()), 'Doe');
        craft\helpers\Template::endProfile('template', '__string_template__791380e632e8eac22b4526bb39f0e0d5');
    }

    public function getTemplateName()
    {
        return '__string_template__791380e632e8eac22b4526bb39f0e0d5';
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
        return new Source('{{ array|indexOf("Doe") }}', '__string_template__791380e632e8eac22b4526bb39f0e0d5', '');
    }
}
