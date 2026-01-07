<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__1d14c964180a029297b8eb43f13c44eb */
class __TwigTemplate_53c270431d157107ba3a028fd2aafd96 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1d14c964180a029297b8eb43f13c44eb');
        // line 1
        echo isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 1, $this->source);
        })();
        echo ' | ';
        echo craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentSite']) || array_key_exists('currentSite', $context) ? $context['currentSite'] : (function () {
            throw new RuntimeError('Variable "currentSite" does not exist.', 1, $this->source);
        })()), 'handle', []);
        echo ' ';
        echo isset($context['currentSite']) || array_key_exists('currentSite', $context) ? $context['currentSite'] : (function () {
            throw new RuntimeError('Variable "currentSite" does not exist.', 1, $this->source);
        })();
        echo ' ';
        echo isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
            throw new RuntimeError('Variable "siteUrl" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__1d14c964180a029297b8eb43f13c44eb');
    }

    public function getTemplateName()
    {
        return '__string_template__1d14c964180a029297b8eb43f13c44eb';
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
        return new Source('{{ systemName }} | {{ currentSite.handle }} {{ currentSite }} {{ siteUrl }}', '__string_template__1d14c964180a029297b8eb43f13c44eb', '');
    }
}
