<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/widgets/NewUsers/settings.twig */
class __TwigTemplate_4a3efdac9c8a0e66942c9d1cd3f14cf8 extends Template
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

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/widgets/NewUsers/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/widgets/NewUsers/settings.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 3
        yield CoreExtension::callMacro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Date Range', 'app'), 'id' => 'dateRange', 'name' => 'dateRange', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 7
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 7, $this->source);
            })()), 'dateRange', [], 'any', false, false, false, 7), 'options' => [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Last {num, number} {num, plural, =1{day} other{days}}', 'app', ['num' => 7]), 'value' => 'd7'], ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Last {num, number} {num, plural, =1{day} other{days}}', 'app', ['num' => 30]), 'value' => 'd30'], ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Last Week', 'app'), 'value' => 'lastweek'], ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Last Month', 'app'), 'value' => 'lastmonth']], 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 26
                (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                    throw new RuntimeError('Variable "widget" does not exist.', 26, $this->source);
                })()), 'getErrors', ['dateRange'], 'method', false, false, false, 26)]], 3, $context, $this->getSourceContext());
        // line 27
        yield '


';
        // line 30
        $context['userGroups'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 30, $this->source);
        })()), 'app', [], 'any', false, false, false, 30), 'userGroups', [], 'any', false, false, false, 30), 'getAllGroups', [], 'method', false, false, false, 30);
        // line 31
        yield '
';
        // line 32
        if (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['userGroups']) || array_key_exists('userGroups', $context) ? $context['userGroups'] : (function () {
            throw new RuntimeError('Variable "userGroups" does not exist.', 32, $this->source);
        })())) > 0)) {
            // line 33
            yield '
    ';
            // line 34
            $context['userGroupsOptions'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('All', 'app'), 'value' => '']];
            // line 35
            yield '    ';
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['userGroups']) || array_key_exists('userGroups', $context) ? $context['userGroups'] : (function () {
                throw new RuntimeError('Variable "userGroups" does not exist.', 35, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['userGroup']) {
                // line 36
                yield '        ';
                $context['userGroupsOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['userGroupsOptions']) || array_key_exists('userGroupsOptions', $context) ? $context['userGroupsOptions'] : (function () {
                    throw new RuntimeError('Variable "userGroupsOptions" does not exist.', 36, $this->source);
                })()), [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['userGroup'], 'name', [], 'any', false, false, false, 36), 'site'), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['userGroup'], 'id', [], 'any', false, false, false, 36)]]);
                // line 37
                yield '    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['userGroup'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 38
            yield '
    ';
            // line 39
            yield CoreExtension::callMacro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Group', 'app'), 'id' => 'userGroupId', 'name' => 'userGroupId', 'value' => craft\helpers\Template::attribute($this->env, $this->source,             // line 43
                (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                    throw new RuntimeError('Variable "widget" does not exist.', 43, $this->source);
                })()), 'userGroupId', [], 'any', false, false, false, 43), 'options' =>             // line 44
(isset($context['userGroupsOptions']) || array_key_exists('userGroupsOptions', $context) ? $context['userGroupsOptions'] : (function () {
    throw new RuntimeError('Variable "userGroupsOptions" does not exist.', 44, $this->source);
})()), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,             // line 45
    (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
        throw new RuntimeError('Variable "widget" does not exist.', 45, $this->source);
    })()), 'getErrors', ['userGroupId'], 'method', false, false, false, 45)]], 39, $context, $this->getSourceContext());
            // line 46
            yield '

';
        }
        craft\helpers\Template::endProfile('template', '_components/widgets/NewUsers/settings.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/widgets/NewUsers/settings.twig';
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
        return [91 => 46,  89 => 45,  88 => 44,  87 => 43,  86 => 39,  83 => 38,  77 => 37,  74 => 36,  69 => 35,  67 => 34,  64 => 33,  62 => 32,  59 => 31,  57 => 30,  52 => 27,  50 => 26,  49 => 7,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/forms\" as forms %}

{{ forms.selectField({
    label: \"Date Range\"|t('app'),
    id: 'dateRange',
    name: 'dateRange',
    value: widget.dateRange,
    options: [
    {
        label: 'Last {num, number} {num, plural, =1{day} other{days}}'|t('app', {num: 7}),
        value: 'd7'
    },
    {
        label: 'Last {num, number} {num, plural, =1{day} other{days}}'|t('app', {num: 30}),
        value: 'd30'
    },
    {
        label: 'Last Week'|t('app'),
        value: 'lastweek',
    },
    {
        label: 'Last Month'|t('app'),
        value: 'lastmonth',
    },
    ],
    errors: widget.getErrors('dateRange')
}) }}


{% set userGroups = craft.app.userGroups.getAllGroups() %}

{% if userGroups|length > 0 %}

    {% set userGroupsOptions = [{ label: 'All'|t('app'), value: '' }] %}
    {% for userGroup in userGroups %}
        {% set userGroupsOptions = userGroupsOptions|merge([{ label: userGroup.name|t('site'), value: userGroup.id }]) %}
    {% endfor %}

    {{ forms.selectField({
        label: \"User Group\"|t('app'),
        id: 'userGroupId',
        name: 'userGroupId',
        value: widget.userGroupId,
        options: userGroupsOptions,
        errors: widget.getErrors('userGroupId')
    }) }}

{% endif %}
", '_components/widgets/NewUsers/settings.twig', '/tmp/packages/craft5/src/templates/_components/widgets/NewUsers/settings.twig');
    }
}
