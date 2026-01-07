<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__48c8b474a7ef078b1cee3aecdfa4364e */
class __TwigTemplate_975ed6b2625f49403ba2fcc95576414c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__48c8b474a7ef078b1cee3aecdfa4364e');
        // line 1
        echo twig_join_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'foo', 1 => 'bar', 2 => 'baz'], function ($__i__) use ($context) {
            $context['i'] = $__i__;

            return (isset($context['i']) || array_key_exists('i', $context) ? $context['i'] : (function () {
                throw new RuntimeError('Variable "i" does not exist.', 1, $this->source);
            })()) != 'baz';
        }), ' ');
        craft\helpers\Template::endProfile('template', '__string_template__48c8b474a7ef078b1cee3aecdfa4364e');
    }

    public function getTemplateName()
    {
        return '__string_template__48c8b474a7ef078b1cee3aecdfa4364e';
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
        return new Source('{{ ["foo", "bar", "baz"]|filter(i => i != "baz")|join(" ") }}', '__string_template__48c8b474a7ef078b1cee3aecdfa4364e', '');
    }
}
