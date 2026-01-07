<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__1f7b9b1a44c367b20a102f8399b1370e */
class __TwigTemplate_07bcbe2369169c56322abee9b91d10b8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1f7b9b1a44c367b20a102f8399b1370e');
        // line 1
        echo twig_join_filter(craft\helpers\ArrayHelper::getColumn($this->extensions['craft\web\twig\Extension']->multisortFilter([0 => ['k' => 'foo'], 1 => ['k' => 'bar'], 2 => ['k' => 'baz']], 'k'), 'k'), ' ');
        craft\helpers\Template::endProfile('template', '__string_template__1f7b9b1a44c367b20a102f8399b1370e');
    }

    public function getTemplateName()
    {
        return '__string_template__1f7b9b1a44c367b20a102f8399b1370e';
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
        return new Source('{{ [{k:"foo"},{k:"bar"},{k:"baz"}]|multisort("k")|column("k")|join(" ") }}', '__string_template__1f7b9b1a44c367b20a102f8399b1370e', '');
    }
}
