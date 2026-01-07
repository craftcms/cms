<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/global-sidebar */
class __TwigTemplate_1334d11e89856aa1efec7715a951b778 extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/global-sidebar');
        // line 1
        echo '<header id="global-sidebar" class="sidebar">
    <a id="system-info" href="';
        // line 2
        echo twig_escape_filter($this->env, (isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
            throw new RuntimeError('Variable "siteUrl" does not exist.', 2, $this->source);
        })()), 'html', null, true);
        echo '" rel="noopener" target="_blank" title="';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('View site', 'app'), 'html', null, true);
        echo '">
        <div id="site-icon">
            ';
        // line 4
        if ((isset($context['hasSystemIcon']) || array_key_exists('hasSystemIcon', $context) ? $context['hasSystemIcon'] : (function () {
            throw new RuntimeError('Variable "hasSystemIcon" does not exist.', 4, $this->source);
        })())) {
            // line 5
            echo '                <img src="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 5, $this->source);
            })()), 'rebrand', []), 'icon', []), 'url', []), 'html', null, true);
            echo '" alt="">
            ';
        } else {
            // line 7
            echo '                ';
            echo craft\helpers\Cp::iconSvg('c-outline');
            echo '
            ';
        }
        // line 9
        echo '        </div>
        <div id="system-name">
            <span class="h2">';
        // line 11
        echo twig_escape_filter($this->env, (isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 11, $this->source);
        })()), 'html', null, true);
        echo '</span>
        </div>
    </a>

    ';
        // line 15
        echo craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'cp', []), 'nav', [], 'method');
        echo '

    ';
        // line 17
        if ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 17, $this->source);
        })()), 'admin', []) && (isset($context['devMode']) || array_key_exists('devMode', $context) ? $context['devMode'] : (function () {
            throw new RuntimeError('Variable "devMode" does not exist.', 17, $this->source);
        })()))) {
            // line 18
            echo '        ';
            $context['devModeText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Craft CMS is running in Dev Mode.', 'app');
            // line 19
            echo '        <div id="devmode">
            ';
            // line 20
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'text' =>             // line 22
(isset($context['devModeText']) || array_key_exists('devModeText', $context) ? $context['devModeText'] : (function () {
    throw new RuntimeError('Variable "devModeText" does not exist.', 22, $this->source);
})()), ]);
            // line 23
            echo '
        </div>
    ';
        }
        // line 26
        echo '</header>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/global-sidebar');
    }

    public function getTemplateName()
    {
        return '_layouts/components/global-sidebar';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [94 => 26,  89 => 23,  87 => 22,  86 => 20,  83 => 19,  80 => 18,  78 => 17,  73 => 15,  66 => 11,  62 => 9,  56 => 7,  50 => 5,  48 => 4,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<header id=\"global-sidebar\" class=\"sidebar\">
    <a id=\"system-info\" href=\"{{ siteUrl }}\" rel=\"noopener\" target=\"_blank\" title=\"{{ 'View site'|t('app') }}\">
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

    {{ craft.cp.nav() | raw }}

    {% if currentUser.admin and devMode %}
        {% set devModeText = 'Craft CMS is running in Dev Mode.'|t('app') %}
        <div id=\"devmode\">
            {{ tag('span', {
                class: 'visually-hidden',
                text: devModeText
            }) }}
        </div>
    {% endif %}
</header>
", '_layouts/components/global-sidebar', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/global-sidebar.twig');
    }
}
