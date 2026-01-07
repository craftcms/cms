<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__adec945b087993e586e200231495e171 */
class __TwigTemplate_3f802176d2fae0b297de182ffe4442b8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__adec945b087993e586e200231495e171');
        // line 1
        echo craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'app', []), 'user', []), 'getIdentity', [], 'method'), 'firstName', []);
        craft\helpers\Template::endProfile('template', '__string_template__adec945b087993e586e200231495e171');
    }

    public function getTemplateName()
    {
        return '__string_template__adec945b087993e586e200231495e171';
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
        return new Source('{{ craft.app.user.getIdentity().firstName }}', '__string_template__adec945b087993e586e200231495e171', '');
    }
}
