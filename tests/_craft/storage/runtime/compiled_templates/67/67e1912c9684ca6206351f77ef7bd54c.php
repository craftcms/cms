<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__862f35f17a7a6a1668b21a7890d7e169 */
class __TwigTemplate_faf018e1fb4650322aea73050fb76b79 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__862f35f17a7a6a1668b21a7890d7e169');
        // line 1
        echo isset($context['fromName']) || array_key_exists('fromName', $context) ? $context['fromName'] : (function () {
            throw new RuntimeError('Variable "fromName" does not exist.', 1, $this->source);
        })();
        echo ' || ';
        echo isset($context['fromEmail']) || array_key_exists('fromEmail', $context) ? $context['fromEmail'] : (function () {
            throw new RuntimeError('Variable "fromEmail" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__862f35f17a7a6a1668b21a7890d7e169');
    }

    public function getTemplateName()
    {
        return '__string_template__862f35f17a7a6a1668b21a7890d7e169';
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
        return new Source('{{fromName}} || {{fromEmail}}', '__string_template__862f35f17a7a6a1668b21a7890d7e169', '');
    }
}
