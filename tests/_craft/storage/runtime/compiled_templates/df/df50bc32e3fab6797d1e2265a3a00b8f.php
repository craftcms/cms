<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__4124ceded1f394422fd5dc2e1c7fbc87 */
class __TwigTemplate_6ab5634559cca149d373057fa3f9f948 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4124ceded1f394422fd5dc2e1c7fbc87');
        // line 1
        echo 'Hey ';
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['user']) || array_key_exists('user', $context) ? $context['user'] : (function () {
            throw new RuntimeError('Variable "user" does not exist.', 1, $this->source);
        })()), 'friendlyName', []));
        echo ',

Thanks for creating an account with ';
        // line 3
        echo isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 3, $this->source);
        })();
        echo '! To activate your account, click the following link:

<';
        // line 5
        echo isset($context['link']) || array_key_exists('link', $context) ? $context['link'] : (function () {
            throw new RuntimeError('Variable "link" does not exist.', 5, $this->source);
        })();
        echo '>

If you were not expecting this email, just ignore it.';
        craft\helpers\Template::endProfile('template', '__string_template__4124ceded1f394422fd5dc2e1c7fbc87');
    }

    public function getTemplateName()
    {
        return '__string_template__4124ceded1f394422fd5dc2e1c7fbc87';
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

Thanks for creating an account with {{systemName}}! To activate your account, click the following link:

<{{link}}>

If you were not expecting this email, just ignore it.', '__string_template__4124ceded1f394422fd5dc2e1c7fbc87', '');
    }
}
