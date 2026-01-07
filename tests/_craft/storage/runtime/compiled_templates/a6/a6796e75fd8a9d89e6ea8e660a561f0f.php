<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__1cc9ba49df6d73e258a863804231e4ca */
class __TwigTemplate_c39e46a6afd64ddd77e26b86d40ac2ba extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1cc9ba49df6d73e258a863804231e4ca');
        // line 1
        echo twig_join_filter(twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->groupFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'users', [], 'method'), 'id', [0 => 1], 'method'), 'all', [], 'method'), function ($__u__) use ($context) {
            $context['u'] = $__u__;

            return craft\helpers\Template::attribute($this->env, $this->source, (isset($context['u']) || array_key_exists('u', $context) ? $context['u'] : (function () {
                throw new RuntimeError('Variable "u" does not exist.', 1, $this->source);
            })()), 'username', []);
        })), ',');
        craft\helpers\Template::endProfile('template', '__string_template__1cc9ba49df6d73e258a863804231e4ca');
    }

    public function getTemplateName()
    {
        return '__string_template__1cc9ba49df6d73e258a863804231e4ca';
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
        return new Source('{{ craft.users().id(1).all()|group(u => u.username)|keys|join(",") }}', '__string_template__1cc9ba49df6d73e258a863804231e4ca', '');
    }
}
