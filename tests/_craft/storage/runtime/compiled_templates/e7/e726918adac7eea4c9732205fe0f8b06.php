<?php

use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/utilities/DbBackup.twig */
class __TwigTemplate_56a508ef7d4f5e79a5eef539268c5ee1 extends Template
{
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/utilities/DbBackup.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/DbBackup.twig', 1)->unwrap();
        // line 2
        yield '
<form id="db-backup" class="utility" method="post" accept-charset="UTF-8">
    ';
        // line 4
        yield craft\helpers\Html::actionInput('utilities/db-backup-perform-action');
        yield '
    ';
        // line 5
        yield craft\helpers\Html::csrfInput();
        yield '

    ';
        // line 7
        yield CoreExtension::callMacro($macros['forms'], 'macro_checkbox', [['name' => 'downloadBackup', 'id' => 'download-backup', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Download backup', 'app'), 'checked' => true]], 7, $context, $this->getSourceContext());
        // line 12
        yield '

    <div class="buttons">
        <button type="submit" class="btn submit">';
        // line 15
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Backup', 'app'), 'html', null, true);
        yield '</button>
        <div class="utility-status"></div>
    </div>
</form>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/DbBackup.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/DbBackup.twig';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return [65 => 15,  60 => 12,  58 => 7,  53 => 5,  49 => 4,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
", '_components/utilities/DbBackup.twig', '/tmp/packages/craft5/src/templates/_components/utilities/DbBackup.twig');
    }
}
