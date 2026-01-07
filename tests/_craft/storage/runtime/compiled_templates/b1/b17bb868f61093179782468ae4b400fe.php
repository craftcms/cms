<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__d521d5e4c47799ef6a24d44b3058af2e */
class __TwigTemplate_42d813fb86e56084ef176b5b82e9fb00 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__d521d5e4c47799ef6a24d44b3058af2e');
        // line 1
        echo 'Hey ';
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['user']) || array_key_exists('user', $context) ? $context['user'] : (function () {
            throw new RuntimeError('Variable "user" does not exist.', 1, $this->source);
        })()), 'friendlyName', []));
        echo ',

To reset your ';
        // line 3
        echo isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 3, $this->source);
        })();
        echo ' password, click on this link:

<';
        // line 5
        echo isset($context['link']) || array_key_exists('link', $context) ? $context['link'] : (function () {
            throw new RuntimeError('Variable "link" does not exist.', 5, $this->source);
        })();
        echo '>

If you were not expecting this email, just ignore it.';
        craft\helpers\Template::endProfile('template', '__string_template__d521d5e4c47799ef6a24d44b3058af2e');
    }

    public function getTemplateName()
    {
        return '__string_template__d521d5e4c47799ef6a24d44b3058af2e';
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
        return new Source('Hey {{user.friendlyName|e}},

To reset your {{systemName}} password, click on this link:

<{{link}}>

If you were not expecting this email, just ignore it.', '__string_template__d521d5e4c47799ef6a24d44b3058af2e', '');
    }
}
