<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__e5baa3cda4b41a484c8a3e37e971b8c2 */
class __TwigTemplate_4dd25443a541353a25cd05dab02fd59f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__e5baa3cda4b41a484c8a3e37e971b8c2');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->addressFilter((isset($context['myAddress']) || array_key_exists('myAddress', $context) ? $context['myAddress'] : (function () {
            throw new RuntimeError('Variable "myAddress" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__e5baa3cda4b41a484c8a3e37e971b8c2');
    }

    public function getTemplateName()
    {
        return '__string_template__e5baa3cda4b41a484c8a3e37e971b8c2';
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
        return new Source('{{ myAddress|address }}', '__string_template__e5baa3cda4b41a484c8a3e37e971b8c2', '');
    }
}
