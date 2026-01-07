<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/DbBackup.twig */
class __TwigTemplate_41a8e36560867b6206aceb0ba5be28b6 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/DbBackup.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/DbBackup.twig', 1)->unwrap();
        // line 2
        echo '
<form id="db-backup" class="utility" method="post" accept-charset="UTF-8">
    ';
        // line 4
        echo craft\helpers\Html::actionInput('utilities/db-backup-perform-action');
        echo '
    ';
        // line 5
        echo craft\helpers\Html::csrfInput();
        echo '

    ';
        // line 7
        echo twig_call_macro($macros['forms'], 'macro_checkbox', [['name' => 'downloadBackup', 'id' => 'download-backup', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Download backup', 'app'), 'checked' => true]], 7, $context, $this->getSourceContext());
        // line 12
        echo '

    <div class="buttons">
        <button type="submit" class="btn submit">';
        // line 15
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Backup', 'app'), 'html', null, true);
        echo '</button>
        <div class="utility-status"></div>
    </div>
</form>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/DbBackup.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/DbBackup.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [60 => 15,  55 => 12,  53 => 7,  48 => 5,  44 => 4,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import \"_includes/forms\" as forms %}

<form id=\"db-backup\" class=\"utility\" method=\"post\" accept-charset=\"UTF-8\">
    {{ actionInput('utilities/db-backup-perform-action') }}
    {{ csrfInput() }}

    {{ forms.checkbox({
        name: 'downloadBackup',
        id: 'download-backup',
        label: 'Download backup'|t('app'),
        checked: true,
    }) }}

    <div class=\"buttons\">
        <button type=\"submit\" class=\"btn submit\">{{ 'Backup'|t('app') }}</button>
        <div class=\"utility-status\"></div>
    </div>
</form>
", '_components/utilities/DbBackup.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/DbBackup.twig');
    }
}
