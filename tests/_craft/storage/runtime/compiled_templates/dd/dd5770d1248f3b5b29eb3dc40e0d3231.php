<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__2b87412f94a998473bf7808f553e350c */
class __TwigTemplate_91a552a8d371836a999a5fbfd4d095e8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2b87412f94a998473bf7808f553e350c');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->indexOfFilter((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()), 'Smith');
        craft\helpers\Template::endProfile('template', '__string_template__2b87412f94a998473bf7808f553e350c');
    }

    public function getTemplateName()
    {
        return '__string_template__2b87412f94a998473bf7808f553e350c';
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
        return new Source('{{ array|indexOf("Smith") }}', '__string_template__2b87412f94a998473bf7808f553e350c', '');
    }
}
