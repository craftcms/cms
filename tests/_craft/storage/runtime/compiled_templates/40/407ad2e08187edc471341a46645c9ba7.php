<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__fbe6b597421323ac1fd4486cf1a0909f */
class __TwigTemplate_87a10e66df5f3e8204ad08e7824141f3 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__fbe6b597421323ac1fd4486cf1a0909f');
        // line 1
        echo ($this->env->getTest('missing')->getCallable()((isset($context['foo']) || array_key_exists('foo', $context) ? $context['foo'] : (function () {
            throw new RuntimeError('Variable "foo" does not exist.', 1, $this->source);
        })()))) ? ('yes') : ('no');
        craft\helpers\Template::endProfile('template', '__string_template__fbe6b597421323ac1fd4486cf1a0909f');
    }

    public function getTemplateName()
    {
        return '__string_template__fbe6b597421323ac1fd4486cf1a0909f';
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
        return new Source('{{ foo is missing ? "yes" : "no" }}', '__string_template__fbe6b597421323ac1fd4486cf1a0909f', '');
    }
}
