<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/system-info */
class __TwigTemplate_c0a47fd27310522cfd599add9dfde52a extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/system-info');
        // line 1
        echo '<a id="system-info" href="';
        echo twig_escape_filter($this->env, (isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
            throw new RuntimeError('Variable "siteUrl" does not exist.', 1, $this->source);
        })()), 'html', null, true);
        echo '" rel="noopener" target="_blank" title="';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('View site', 'app'), 'html', null, true);
        echo '">
    <div id="site-icon">
        ';
        // line 3
        if ((isset($context['hasSystemIcon']) || array_key_exists('hasSystemIcon', $context) ? $context['hasSystemIcon'] : (function () {
            throw new RuntimeError('Variable "hasSystemIcon" does not exist.', 3, $this->source);
        })())) {
            // line 4
            echo '            <img src="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
            })()), 'rebrand', []), 'icon', []), 'url', []), 'html', null, true);
            echo '" alt="">
        ';
        } else {
            // line 6
            echo '            ';
            echo craft\helpers\Cp::iconSvg('c-outline');
            echo '
        ';
        }
        // line 8
        echo '    </div>
    <div id="system-name">
        <span class="h2">';
        // line 10
        echo twig_escape_filter($this->env, (isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 10, $this->source);
        })()), 'html', null, true);
        echo '</span>
    </div>
</a>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/system-info');
    }

    public function getTemplateName()
    {
        return '_layouts/components/system-info';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [64 => 10,  60 => 8,  54 => 6,  48 => 4,  46 => 3,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<a id=\"system-info\" href=\"{{ siteUrl }}\" rel=\"noopener\" target=\"_blank\" title=\"{{ 'View site'|t('app') }}\">
    <div id=\"site-icon\">
        {% if hasSystemIcon %}
            <img src=\"{{ craft.rebrand.icon.url }}\" alt=\"\">
        {% else %}
            {{ iconSvg('c-outline') }}
        {% endif %}
    </div>
    <div id=\"system-name\">
        <span class=\"h2\">{{ systemName }}</span>
    </div>
</a>
", '_layouts/components/system-info', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/system-info.twig');
    }
}
