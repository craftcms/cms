<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__f6ffdc7bf1df3973435470ee15e1dc96 */
class __TwigTemplate_fd7279ae67e25b81eaee5f37917aafb0 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f6ffdc7bf1df3973435470ee15e1dc96');
        // line 1
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'username', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'username', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'username', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'username', []));
        craft\helpers\Template::endProfile('template', '__string_template__f6ffdc7bf1df3973435470ee15e1dc96');
    }

    public function getTemplateName()
    {
        return '__string_template__f6ffdc7bf1df3973435470ee15e1dc96';
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
        return new Source('{{ (_variables.username ?? object.username)|raw }}', '__string_template__f6ffdc7bf1df3973435470ee15e1dc96', '');
    }
}
