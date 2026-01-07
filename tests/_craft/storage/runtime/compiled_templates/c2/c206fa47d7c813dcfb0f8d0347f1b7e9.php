<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/users/fields */
class __TwigTemplate_e293154bd627f4bcc5dd63163bc213e0 extends Template
{
    private readonly Source $source;

    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 3
        return 'settings/users/_layout';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
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
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/users/fields');
    }

    // line 8
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'content');
        // line 9
        yield '    <form method="post" accept-charset="UTF-8" data-saveshortcut data-confirm-unload>
        ';
        // line 10
        yield craft\helpers\Html::actionInput('users/save-field-layout');
        yield '
        ';
        // line 11
        yield craft\helpers\Html::csrfInput();
        yield '

        ';
        // line 13
        yield CoreExtension::callMacro($macros['forms'], 'macro_fieldLayoutDesignerField', [['first' => true, 'fieldLayout' => ((        // line 15
            $context['fieldLayout']) ?? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
            })()), 'app', [], 'any', false, false, false, 15), 'fields', [], 'any', false, false, false, 15), 'getLayoutByType', ['craft\\elements\\User'], 'method', false, false, false, 15)))]], 13, $context, $this->getSourceContext());
        // line 16
        yield '

        <div class="buttons">
            <button type="submit" class="btn submit">';
        // line 19
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Save', 'app'), 'html', null, true);
        yield '</button>
        </div>
    </form>
';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/users/fields';
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
        return [88 => 19,  83 => 16,  81 => 15,  80 => 13,  75 => 11,  71 => 10,  68 => 9,  60 => 8,  54 => 3,  52 => 5,  50 => 4,  48 => 1,  40 => 3];
    }

    public function getSourceContext(): Source
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
", 'settings/users/fields', '/tmp/packages/craft5/src/templates/settings/users/fields.twig');
    }
}
