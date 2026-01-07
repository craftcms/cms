<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__7a610aedeab7f50dc5ba41df95be8c44 */
class __TwigTemplate_e213427b3e8e6ed99a4491a53ded845d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__7a610aedeab7f50dc5ba41df95be8c44');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->replaceFilter('/foo/bar/', ['/foo/' => 'baz'], null, false);
        craft\helpers\Template::endProfile('template', '__string_template__7a610aedeab7f50dc5ba41df95be8c44');
    }

    public function getTemplateName()
    {
        return '__string_template__7a610aedeab7f50dc5ba41df95be8c44';
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
        return new Source('{{ "/foo/bar/"|replace({"/foo/": "baz"}, regex=false) }}', '__string_template__7a610aedeab7f50dc5ba41df95be8c44', '');
    }
}
