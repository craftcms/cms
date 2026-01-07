<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__b6b756dcc3e73146f07577de04a4fc98 */
class __TwigTemplate_8451dc2961690d0425efbed52595eab6 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b6b756dcc3e73146f07577de04a4fc98');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['contents']) || array_key_exists('contents', $context) ? $context['contents'] : (function () {
            throw new RuntimeError('Variable "contents" does not exist.', 1, $this->source);
        })()), null, false);
        craft\helpers\Template::endProfile('template', '__string_template__b6b756dcc3e73146f07577de04a4fc98');
    }

    public function getTemplateName()
    {
        return '__string_template__b6b756dcc3e73146f07577de04a4fc98';
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
        return new Source('{{ svg(contents, namespace=false) }}', '__string_template__b6b756dcc3e73146f07577de04a4fc98', '');
    }
}
