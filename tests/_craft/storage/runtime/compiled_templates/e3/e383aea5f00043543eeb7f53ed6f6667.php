<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* users */
class __TwigTemplate_cf19f43a9c79b96511f68d7241f64010 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'actionButton' => $this->block_actionButton(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 7
        return '_layouts/elementindex';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'users');
        // line 1
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 1, $this->source);
        })()) != (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 1, $this->source);
        })()))) {
            // line 2
            throw new yii\web\NotFoundHttpException;
        }
        // line 5
        \Craft::$app->controller->requirePermission('editUsers');
        // line 8
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Users', 'app');
        // line 9
        $context['elementType'] = 'craft\\elements\\User';
        // line 11
        $context['canHaveDrafts'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 11, $this->source);
        })()), 'users', [], 'method'), 'drafts', [], 'method'), 'draftOf', [0 => false], 'method'), 'savedDraftsOnly', [], 'method'), 'exists', [], 'method');
        // line 19
        if (array_key_exists('source', $context)) {
            // line 20
            ob_start();
            // line 21
            echo '    window.defaultSourceSlug = "';
            echo twig_escape_filter($this->env, twig_escape_filter($this->env, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                throw new RuntimeError('Variable "source" does not exist.', 21, $this->source);
            })()), 'js'), 'html', null, true);
            echo '";
    ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        // line 7
        $this->parent = $this->loadTemplate('_layouts/elementindex', 'users', 7);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'users');
    }

    // line 13
    public function block_actionButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 14
        echo '    ';
        if (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 14, $this->source);
        })()), 'can', [0 => 'registerUsers'], 'method')) {
            // line 15
            echo '        <a class="btn submit add icon" href="';
            echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('users/new'), 'html', null, true);
            echo '">';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New user', 'app'), 'html', null, true);
            echo '</a>
    ';
        }
        craft\helpers\Template::endProfile('block', 'actionButton');
    }

    public function getTemplateName()
    {
        return 'users';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [81 => 15,  78 => 14,  73 => 13,  67 => 7,  60 => 21,  58 => 20,  56 => 19,  54 => 11,  52 => 9,  50 => 8,  48 => 5,  45 => 2,  43 => 1,  35 => 7];
    }

    public function getSourceContext()
    {
        return new Source("{% if CraftEdition != CraftPro %}
    {% exit 404 %}
{% endif %}

{% requirePermission 'editUsers' %}

{% extends \"_layouts/elementindex\" %}
{% set title = \"Users\"|t('app') %}
{% set elementType = 'craft\\\\elements\\\\User' %}

{% set canHaveDrafts = craft.users().drafts().draftOf(false).savedDraftsOnly().exists() %}

{% block actionButton %}
    {% if currentUser.can('registerUsers') %}
        <a class=\"btn submit add icon\" href=\"{{ url('users/new') }}\">{{ \"New user\"|t('app') }}</a>
    {% endif %}
{% endblock %}

{% if source is defined %}
    {% js %}
    window.defaultSourceSlug = \"{{ source|e('js') }}\";
    {% endjs %}
{% endif %}
", 'users', '/Users/brianhanson/Development/craft5/src/templates/users/index.twig');
    }
}
