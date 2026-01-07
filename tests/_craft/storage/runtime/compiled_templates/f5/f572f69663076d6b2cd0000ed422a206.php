<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__cc1f49cbb5ab76f6aa6efad9548cde79 */
class __TwigTemplate_0244d33b6bb4ae223812e755183bf6ef extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cc1f49cbb5ab76f6aa6efad9548cde79');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['contents']) || array_key_exists('contents', $context) ? $context['contents'] : (function () {
            throw new RuntimeError('Variable "contents" does not exist.', 1, $this->source);
        })()), null, null, 'foobar');
        craft\helpers\Template::endProfile('template', '__string_template__cc1f49cbb5ab76f6aa6efad9548cde79');
    }

    public function getTemplateName()
    {
        return '__string_template__cc1f49cbb5ab76f6aa6efad9548cde79';
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
        return new Source('{{ svg(contents, class="foobar") }}', '__string_template__cc1f49cbb5ab76f6aa6efad9548cde79', '');
    }
}
