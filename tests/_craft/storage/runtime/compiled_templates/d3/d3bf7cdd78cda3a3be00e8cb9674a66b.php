<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__d564fb469ef49d27edba39bd2b086dc3 */
class __TwigTemplate_9bec4868b5124c61a36d74e6a4214cf6 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__d564fb469ef49d27edba39bd2b086dc3');
        // line 1
        echo craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'app', []), 'request', []), 'getRawBody', [], 'method');
        craft\helpers\Template::endProfile('template', '__string_template__d564fb469ef49d27edba39bd2b086dc3');
    }

    public function getTemplateName()
    {
        return '__string_template__d564fb469ef49d27edba39bd2b086dc3';
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
        return new Source('{{ craft.app.request.getRawBody() }}', '__string_template__d564fb469ef49d27edba39bd2b086dc3', '');
    }
}
