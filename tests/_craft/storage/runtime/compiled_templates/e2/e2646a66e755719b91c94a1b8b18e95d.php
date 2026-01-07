<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/users/fields */
class __TwigTemplate_e8b64f30fa4843845f68c776a1ca01ac extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 3
        return 'settings/users/_layout';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', 'settings/users/fields');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['selectedNavItem'] = 'fields';
        // line 5
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', 'settings/users/fields', 5)->unwrap();
        // line 3
        $this->parent = $this->loadTemplate('settings/users/_layout', 'settings/users/fields', 3);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/users/fields');
    }

    // line 8
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'content');
        // line 9
        echo '    <form method="post" accept-charset="UTF-8" data-saveshortcut data-confirm-unload>
        ';
        // line 10
        echo craft\helpers\Html::actionInput('users/save-field-layout');
        echo '
        ';
        // line 11
        echo craft\helpers\Html::csrfInput();
        echo '

        ';
        // line 13
        echo twig_call_macro($macros['forms'], 'macro_fieldLayoutDesignerField', [['first' => true, 'fieldLayout' => ((        // line 15
            $context['fieldLayout']) ?? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
            })()), 'app', []), 'fields', []), 'getLayoutByType', [0 => 'craft\\elements\\User'], 'method')))]], 13, $context, $this->getSourceContext());
        // line 16
        echo '

        <div class="buttons">
            <button type="submit" class="btn submit">';
        // line 19
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Save', 'app'), 'html', null, true);
        echo '</button>
        </div>
    </form>
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/users/fields';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [80 => 19,  75 => 16,  73 => 15,  72 => 13,  67 => 11,  63 => 10,  60 => 9,  55 => 8,  49 => 3,  47 => 5,  45 => 4,  43 => 1,  35 => 3];
    }

    public function getSourceContext()
    {
        return new Source("{% requireAdmin %}

{% extends \"settings/users/_layout\" %}
{% set selectedNavItem = 'fields' %}
{% import \"_includes/forms\" as forms %}


{% block content %}
    <form method=\"post\" accept-charset=\"UTF-8\" data-saveshortcut data-confirm-unload>
        {{ actionInput('users/save-field-layout') }}
        {{ csrfInput() }}

        {{ forms.fieldLayoutDesignerField({
            first: true,
            fieldLayout: fieldLayout ?? craft.app.fields.getLayoutByType('craft\\\\elements\\\\User'),
        }) }}

        <div class=\"buttons\">
            <button type=\"submit\" class=\"btn submit\">{{ 'Save'|t('app') }}</button>
        </div>
    </form>
{% endblock %}
", 'settings/users/fields', '/Users/brianhanson/Development/craft5/src/templates/settings/users/fields.twig');
    }
}
