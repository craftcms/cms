<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__587b4fd843aee497bf5f6f229f603ed1 */
class __TwigTemplate_364fb22a1505578c33743d976754c038 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__587b4fd843aee497bf5f6f229f603ed1');
        // line 1
        echo isset($context['arg1']) || array_key_exists('arg1', $context) ? $context['arg1'] : (function () {
            throw new RuntimeError('Variable "arg1" does not exist.', 1, $this->source);
        })();
        echo '-';
        echo isset($context['arg2']) || array_key_exists('arg2', $context) ? $context['arg2'] : (function () {
            throw new RuntimeError('Variable "arg2" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__587b4fd843aee497bf5f6f229f603ed1');
    }

    public function getTemplateName()
    {
        return '__string_template__587b4fd843aee497bf5f6f229f603ed1';
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
        return new Source('{{ arg1 }}-{{ arg2 }}', '__string_template__587b4fd843aee497bf5f6f229f603ed1', '');
    }
}
