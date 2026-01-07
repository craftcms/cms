<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__a35b8b834d90b0d7c4c496cb3c771810 */
class __TwigTemplate_80e1dc0b1d2588fd54a870ff3f0d2fd6 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__a35b8b834d90b0d7c4c496cb3c771810');
        // line 1
        echo isset($context['fromEmail']) || array_key_exists('fromEmail', $context) ? $context['fromEmail'] : (function () {
            throw new RuntimeError('Variable "fromEmail" does not exist.', 1, $this->source);
        })();
        echo ' || ';
        echo isset($context['fromName']) || array_key_exists('fromName', $context) ? $context['fromName'] : (function () {
            throw new RuntimeError('Variable "fromName" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__a35b8b834d90b0d7c4c496cb3c771810');
    }

    public function getTemplateName()
    {
        return '__string_template__a35b8b834d90b0d7c4c496cb3c771810';
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
        return new Source('{{fromEmail}} || {{fromName}}', '__string_template__a35b8b834d90b0d7c4c496cb3c771810', '');
    }
}
