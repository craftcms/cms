<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/timeZone */
class __TwigTemplate_307caa6bb196f20ea34a62a920b721aa extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/timeZone');
        // line 1
        $context['id'] ??= 'timezone'.twig_random($this->env);
        // line 2
        echo '
';
        // line 3
        $this->loadTemplate('_includes/forms/selectize', '_includes/forms/timeZone', 3)->display(twig_array_merge($context, ['options' => craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 4
            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
            })()), 'cp', []), 'getTimeZoneOptions', [], 'method'), 'inputAttributes' => ['aria' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Time Zone', 'app')]]]));
        craft\helpers\Template::endProfile('template', '_includes/forms/timeZone');
    }

    public function getTemplateName()
    {
        return '_includes/forms/timeZone';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [44 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% set id = id ?? \"timezone#{random()}\" %}

{% include '_includes/forms/selectize' with {
    options: craft.cp.getTimeZoneOptions(),
    inputAttributes: {
        aria: {
            label: 'Time Zone'|t('app'),
        }
    }
}%}
", '_includes/forms/timeZone', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/timeZone.twig');
    }
}
