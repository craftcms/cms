<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__9ad34281a63eb0e7d547eb6f25feda81 */
class __TwigTemplate_01203409577b864c31e168f8e7820916 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__9ad34281a63eb0e7d547eb6f25feda81');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->replaceFilter('https://foo.com/bar/baz/', '/(http(s?):)?\\/\\/foo\\.com\\/bar\\/baz\\//', 'qux');
        craft\helpers\Template::endProfile('template', '__string_template__9ad34281a63eb0e7d547eb6f25feda81');
    }

    public function getTemplateName()
    {
        return '__string_template__9ad34281a63eb0e7d547eb6f25feda81';
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
        return new Source('{{ "https://foo.com/bar/baz/"|replace("/(http(s?):)?\\\\/\\\\/foo\\\\.com\\\\/bar\\\\/baz\\\\//", "qux") }}', '__string_template__9ad34281a63eb0e7d547eb6f25feda81', '');
    }
}
