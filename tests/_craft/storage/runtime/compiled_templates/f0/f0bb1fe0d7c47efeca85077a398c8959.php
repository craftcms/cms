<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__6afe6292013668c306397ba7a2132f57 */
class __TwigTemplate_4d7d8c81cf90f2c229dd64307e5851e1 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6afe6292013668c306397ba7a2132f57');
        // line 1
        echo ($this->env->getTest('instance of')->getCallable()((isset($context['foo']) || array_key_exists('foo', $context) ? $context['foo'] : (function () {
            throw new RuntimeError('Variable "foo" does not exist.', 1, $this->source);
        })()), (isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
            throw new RuntimeError('Variable "class" does not exist.', 1, $this->source);
        })()))) ? ('yes') : ('no');
        craft\helpers\Template::endProfile('template', '__string_template__6afe6292013668c306397ba7a2132f57');
    }

    public function getTemplateName()
    {
        return '__string_template__6afe6292013668c306397ba7a2132f57';
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
        return new Source('{{ foo is instance of(class) ? "yes" : "no" }}', '__string_template__6afe6292013668c306397ba7a2132f57', '');
    }
}
