<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__9ba7a7b981bb6dec1843282c75f078d8 */
class __TwigTemplate_761accd27abc432160c630163cc446f5 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__9ba7a7b981bb6dec1843282c75f078d8');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->replaceFilter('foo bar baz', 'bar', 'qux');
        craft\helpers\Template::endProfile('template', '__string_template__9ba7a7b981bb6dec1843282c75f078d8');
    }

    public function getTemplateName()
    {
        return '__string_template__9ba7a7b981bb6dec1843282c75f078d8';
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
        return new Source('{{ "foo bar baz"|replace("bar", "qux") }}', '__string_template__9ba7a7b981bb6dec1843282c75f078d8', '');
    }
}
