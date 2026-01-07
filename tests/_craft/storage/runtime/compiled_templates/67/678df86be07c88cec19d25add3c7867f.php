<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__f7cdef0345ea410a24a90c3e627b37c3 */
class __TwigTemplate_4cc89578d83caa9fc86d5ef18f7b6ce0 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f7cdef0345ea410a24a90c3e627b37c3');
        // line 1
        echo 'Hallo ';
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['user']) || array_key_exists('user', $context) ? $context['user'] : (function () {
            throw new RuntimeError('Variable "user" does not exist.', 1, $this->source);
        })()), 'friendlyName', []));
        echo ',

Bedankt voor het maken van een account op ';
        // line 3
        echo isset($context['siteName']) || array_key_exists('siteName', $context) ? $context['siteName'] : (function () {
            throw new RuntimeError('Variable "siteName" does not exist.', 3, $this->source);
        })();
        echo '! Klik op de volgende link om je account te activeren:

<';
        // line 5
        echo isset($context['link']) || array_key_exists('link', $context) ? $context['link'] : (function () {
            throw new RuntimeError('Variable "link" does not exist.', 5, $this->source);
        })();
        echo '>

Als je deze e-mail niet verwachtte, kun je hem gewoon negeren.';
        craft\helpers\Template::endProfile('template', '__string_template__f7cdef0345ea410a24a90c3e627b37c3');
    }

    public function getTemplateName()
    {
        return '__string_template__f7cdef0345ea410a24a90c3e627b37c3';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [49 => 5,  44 => 3,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('Hallo {{user.friendlyName|e}},

Bedankt voor het maken van een account op {{siteName}}! Klik op de volgende link om je account te activeren:

<{{link}}>

Als je deze e-mail niet verwachtte, kun je hem gewoon negeren.', '__string_template__f7cdef0345ea410a24a90c3e627b37c3', '');
    }
}
