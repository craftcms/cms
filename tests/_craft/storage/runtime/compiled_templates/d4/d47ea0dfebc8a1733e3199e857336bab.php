<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__bf9e84c229de9bca6c400bc852f92aa9 */
class __TwigTemplate_0dd64bd9a570fcf8d874169b07424a02 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__bf9e84c229de9bca6c400bc852f92aa9');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->datetimeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'icu:YYYY-MM-dd HH:mm:ss');
        craft\helpers\Template::endProfile('template', '__string_template__bf9e84c229de9bca6c400bc852f92aa9');
    }

    public function getTemplateName()
    {
        return '__string_template__bf9e84c229de9bca6c400bc852f92aa9';
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
        return new Source('{{ d|datetime("icu:YYYY-MM-dd HH:mm:ss") }}', '__string_template__bf9e84c229de9bca6c400bc852f92aa9', '');
    }
}
