<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/FindAndReplace.twig */
class __TwigTemplate_82d0e6a8007235d0967c5d182c7cb782 extends Template
{
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/utilities/FindAndReplace.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/FindAndReplace.twig', 1)->unwrap();
        // line 2
        echo '
<form id="find-replace" class="utility" method="post" accept-charset="UTF-8">
    ';
        // line 4
        echo craft\helpers\Html::actionInput('utilities/find-and-replace-perform-action');
        echo '
    ';
        // line 5
        echo craft\helpers\Html::csrfInput();
        echo '

    ';
        // line 7
        echo twig_call_macro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Find Text', 'app'), 'first' => true, 'name' => 'find']], 7, $context, $this->getSourceContext());
        // line 11
        echo '

    ';
        // line 13
        echo twig_call_macro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Replace Text', 'app'), 'name' => 'replace']], 13, $context, $this->getSourceContext());
        // line 16
        echo '

    <div class="buttons">
        <button type="submit" class="btn submit">';
        // line 19
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Find and Replace', 'app'), 'html', null, true);
        echo '</button>
        <div class="utility-status"></div>
    </div>
</form>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/FindAndReplace.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/FindAndReplace.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [66 => 19,  61 => 16,  59 => 13,  55 => 11,  53 => 7,  48 => 5,  44 => 4,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import \"_includes/forms\" as forms %}

<form id=\"find-replace\" class=\"utility\" method=\"post\" accept-charset=\"UTF-8\">
    {{ actionInput('utilities/find-and-replace-perform-action') }}
    {{ csrfInput() }}

    {{ forms.textField({
        label: \"Find Text\"|t('app'),
        first: true,
        name: 'find'
    }) }}

    {{ forms.textField({
        label: \"Replace Text\"|t('app'),
        name: 'replace'
    }) }}

    <div class=\"buttons\">
        <button type=\"submit\" class=\"btn submit\">{{ 'Find and Replace'|t('app') }}</button>
        <div class=\"utility-status\"></div>
    </div>
</form>
", '_components/utilities/FindAndReplace.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/FindAndReplace.twig');
    }
}
