<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__e542a6b1cdfb78df9705502f93389c34 */
class __TwigTemplate_be841866945a30dad382929bbb86fc29 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__e542a6b1cdfb78df9705502f93389c34');
        // line 1
        echo isset($context['aGlobalSet']) || array_key_exists('aGlobalSet', $context) ? $context['aGlobalSet'] : (function () {
            throw new RuntimeError('Variable "aGlobalSet" does not exist.', 1, $this->source);
        })();
        echo ' | ';
        echo isset($context['aDifferentGlobalSet']) || array_key_exists('aDifferentGlobalSet', $context) ? $context['aDifferentGlobalSet'] : (function () {
            throw new RuntimeError('Variable "aDifferentGlobalSet" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__e542a6b1cdfb78df9705502f93389c34');
    }

    public function getTemplateName()
    {
        return '__string_template__e542a6b1cdfb78df9705502f93389c34';
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
        return new Source('{{ aGlobalSet }} | {{ aDifferentGlobalSet }}', '__string_template__e542a6b1cdfb78df9705502f93389c34', '');
    }
}
