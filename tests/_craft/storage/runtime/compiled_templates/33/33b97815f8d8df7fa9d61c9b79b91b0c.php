<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__74b92a79c5385b76e43188d07275e3d2 */
class __TwigTemplate_2df24decd2b286670600b83b4fd6f5a2 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__74b92a79c5385b76e43188d07275e3d2');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['contents']) || array_key_exists('contents', $context) ? $context['contents'] : (function () {
            throw new RuntimeError('Variable "contents" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__74b92a79c5385b76e43188d07275e3d2');
    }

    public function getTemplateName()
    {
        return '__string_template__74b92a79c5385b76e43188d07275e3d2';
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
        return new Source('{{ svg(contents) }}', '__string_template__74b92a79c5385b76e43188d07275e3d2', '');
    }
}
