<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__fb93d6df0ad08b7622c6417407fd81f4 */
class __TwigTemplate_c9b023ef7aa2a19ec1e1fbdeb3cd2c79 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__fb93d6df0ad08b7622c6417407fd81f4');
        // line 1
        echo craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'firstName', []);
        craft\helpers\Template::endProfile('template', '__string_template__fb93d6df0ad08b7622c6417407fd81f4');
    }

    public function getTemplateName()
    {
        return '__string_template__fb93d6df0ad08b7622c6417407fd81f4';
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
        return new Source('{{ object.firstName}}', '__string_template__fb93d6df0ad08b7622c6417407fd81f4', '');
    }
}
