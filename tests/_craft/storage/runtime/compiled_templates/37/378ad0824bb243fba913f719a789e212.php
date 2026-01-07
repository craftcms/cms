<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0 */
class __TwigTemplate_debedf1ed7da000ba21fc777071219d2 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0');
        // line 1
        echo 'Hey ';
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['user']) || array_key_exists('user', $context) ? $context['user'] : (function () {
            throw new RuntimeError('Variable "user" does not exist.', 1, $this->source);
        })()), 'friendlyName', []));
        echo ',

Please verify your new email address by clicking on this link:

<';
        // line 5
        echo isset($context['link']) || array_key_exists('link', $context) ? $context['link'] : (function () {
            throw new RuntimeError('Variable "link" does not exist.', 5, $this->source);
        })();
        echo '>

If you were not expecting this email, just ignore it.';
        craft\helpers\Template::endProfile('template', '__string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0');
    }

    public function getTemplateName()
    {
        return '__string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [46 => 5,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('Hey {{user.friendlyName|e}},

Please verify your new email address by clicking on this link:

<{{link}}>

If you were not expecting this email, just ignore it.', '__string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0', '');
    }
}
