<?php

use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/utilities/FindAndReplace.twig */
class __TwigTemplate_d7dd0eab927db01128e88b410e3f69e4 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/FindAndReplace.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/FindAndReplace.twig', 1)->unwrap();
        // line 2
        yield '
<form id="find-replace" class="utility" method="post" accept-charset="UTF-8">
    ';
        // line 4
        yield craft\helpers\Html::actionInput('utilities/find-and-replace-perform-action');
        yield '
    ';
        // line 5
        yield craft\helpers\Html::csrfInput();
        yield '

    ';
        // line 7
        yield CoreExtension::callMacro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Find Text', 'app'), 'first' => true, 'name' => 'find']], 7, $context, $this->getSourceContext());
        // line 11
        yield '

    ';
        // line 13
        yield CoreExtension::callMacro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Replace Text', 'app'), 'name' => 'replace']], 13, $context, $this->getSourceContext());
        // line 16
        yield '

    <div class="buttons">
        <button type="submit" class="btn submit">';
        // line 19
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Find and Replace', 'app'), 'html', null, true);
        yield '</button>
        <div class="utility-status"></div>
    </div>
</form>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/FindAndReplace.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/FindAndReplace.twig';
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
        return [71 => 19,  66 => 16,  64 => 13,  60 => 11,  58 => 7,  53 => 5,  49 => 4,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
", '_components/utilities/FindAndReplace.twig', '/tmp/packages/craft5/src/templates/_components/utilities/FindAndReplace.twig');
    }
}
