<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/sites/index.twig */
class __TwigTemplate_3f4a28aea9ceb1df672e8dcd09678ba1 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'actionButton' => $this->block_actionButton(...),
            'sidebar' => $this->block_sidebar(...),
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'settings/sites/index.twig');
        // line 2
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Sites', 'app');
        // line 4
        $context['multiple'] = ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['sites']) || array_key_exists('sites', $context) ? $context['sites'] : (function () {
            throw new RuntimeError('Variable "sites" does not exist.', 4, $this->source);
        })())) > 1);
        // line 5
        $context['canSort'] = ((isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
            throw new RuntimeError('Variable "group" does not exist.', 5, $this->source);
        })()) && (isset($context['multiple']) || array_key_exists('multiple', $context) ? $context['multiple'] : (function () {
            throw new RuntimeError('Variable "multiple" does not exist.', 5, $this->source);
        })()));
        // line 107
        ob_start();
        // line 108
        yield "    new Craft.SitesAdmin();

    new Craft.SiteAdminTable({
        tableSelector: '#sites',
        minItems: 1,
        sortable: true,
        reorderAction: 'sites/reorder-sites',
        deleteAction: 'sites/delete-site',
    });
";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 4]);
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/sites/index.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/sites/index.twig');
    }

    // line 8
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_actionButton(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 9
        yield '    ';
        yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\UrlHelper::url('settings/sites/new', ((        // line 10
            (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                throw new RuntimeError('Variable "group" does not exist.', 10, $this->source);
            })())) ? (['groupId' => craft\helpers\Template::attribute($this->env, $this->source, (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                throw new RuntimeError('Variable "group" does not exist.', 10, $this->source);
            })()), 'id', [], 'any', false, false, false, 10)]) : (null))), 'class' => ['btn', 'submit', 'add', 'icon', ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 11
                (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 11, $this->source);
                })()), 'app', [], 'any', false, false, false, 11), 'sites', [], 'any', false, false, false, 11), 'getRemainingSites', [], 'method', false, false, false, 11)) ? (null) : ('disabled'))], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('New site', 'app')]);
        // line 13
        yield '
';
        craft\helpers\Template::endProfile('block', 'actionButton');
        yield from [];
    }

    // line 17
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_sidebar(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 18
        yield '    <nav>
        <ul id="groups">
            <li><a href="';
        // line 20
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('settings/sites'), 'html', null, true);
        yield '"';
        if (! (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
            throw new RuntimeError('Variable "group" does not exist.', 20, $this->source);
        })())) {
            yield ' class="sel"';
        }
        yield '>';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('All Sites', 'app'), 'html', null, true);
        yield '</a></li>
            ';
        // line 21
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 21, $this->source);
        })()), 'app', [], 'any', false, false, false, 21), 'sites', [], 'any', false, false, false, 21), 'getAllGroups', [], 'method', false, false, false, 21));
        foreach ($context['_seq'] as $context['_key'] => $context['g']) {
            // line 22
            yield '                <li>
                    ';
            // line 23
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\UrlHelper::url('settings/sites', ['groupId' => craft\helpers\Template::attribute($this->env, $this->source,             // line 24
                $context['g'], 'id', [], 'any', false, false, false, 24)]), 'class' => (((            // line 25
                    (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                        throw new RuntimeError('Variable "group" does not exist.', 25, $this->source);
                    })()) && (craft\helpers\Template::attribute($this->env, $this->source, $context['g'], 'id', [], 'any', false, false, false, 25) == craft\helpers\Template::attribute($this->env, $this->source, (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                        throw new RuntimeError('Variable "group" does not exist.', 25, $this->source);
                    })()), 'id', [], 'any', false, false, false, 25)))) ? ('sel') : (false)), 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 26
                        $context['g'], 'name', [], 'any', false, false, false, 26), 'site'), 'data' => ['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 28
                            $context['g'], 'id', [], 'any', false, false, false, 28), 'raw-name' => craft\helpers\Template::attribute($this->env, $this->source,             // line 29
                                $context['g'], 'getName', [false], 'method', false, false, false, 29)]]);
            // line 31
            yield '
                </li>
            ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['g'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 34
        yield '        </ul>
    </nav>

    <div class="buttons">
        <button type="button" id="newgroupbtn" class="btn add icon">';
        // line 38
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New group', 'app'), 'html', null, true);
        yield '</button>

        ';
        // line 40
        if ((isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
            throw new RuntimeError('Variable "group" does not exist.', 40, $this->source);
        })())) {
            // line 41
            yield '            <button type="button" id="groupsettingsbtn" class="btn settings icon menubtn" title="';
            yield 'Settings';
            yield '" aria-label="';
            yield 'Settings';
            yield '"></button>
            <div class="menu">
                <ul>
                    <li><a data-action="rename" role="button">';
            // line 44
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Rename selected group', 'app'), 'html', null, true);
            yield '</a></li>
                    <li><a data-action="delete" role="button"';
            // line 45
            if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['sites']) || array_key_exists('sites', $context) ? $context['sites'] : (function () {
                throw new RuntimeError('Variable "sites" does not exist.', 45, $this->source);
            })()))) {
                yield ' class="disabled" title="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('You can only delete groups that have no sites.', 'app'), 'html', null, true);
                yield '"';
            }
            yield '>';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Delete selected group', 'app'), 'html', null, true);
            yield '</a></li>
                </ul>
            </div>
        ';
        }
        // line 49
        yield '    </div>
';
        craft\helpers\Template::endProfile('block', 'sidebar');
        yield from [];
    }

    // line 53
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 54
        yield '    ';
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['sites']) || array_key_exists('sites', $context) ? $context['sites'] : (function () {
            throw new RuntimeError('Variable "sites" does not exist.', 54, $this->source);
        })()))) {
            // line 55
            yield '        <div class="tablepane">
            <table id="sites" class="data fullwidth">
                <thead>
                    <th scope="col">';
            // line 58
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Name', 'app'), 'html', null, true);
            yield '</th>
                    <th scope="col">';
            // line 59
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Handle', 'app'), 'html', null, true);
            yield '</th>
                    <th scope="col">';
            // line 60
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Language', 'app'), 'html', null, true);
            yield '</th>
                    <th scope="col">';
            // line 61
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Primary', 'app'), 'html', null, true);
            yield '</th>
                    <th scope="col">';
            // line 62
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Base URL', 'app'), 'html', null, true);
            yield '</th>
                    ';
            // line 63
            if (! (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                throw new RuntimeError('Variable "group" does not exist.', 63, $this->source);
            })())) {
                // line 64
                yield '                        <th scope="col">';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Group', 'app'), 'html', null, true);
                yield '</th>
                    ';
            }
            // line 66
            yield '                    ';
            if ((isset($context['canSort']) || array_key_exists('canSort', $context) ? $context['canSort'] : (function () {
                throw new RuntimeError('Variable "canSort" does not exist.', 66, $this->source);
            })())) {
                // line 67
                yield '                        <td class="thin"></td>
                    ';
            }
            // line 69
            yield '                    ';
            if ((isset($context['multiple']) || array_key_exists('multiple', $context) ? $context['multiple'] : (function () {
                throw new RuntimeError('Variable "multiple" does not exist.', 69, $this->source);
            })())) {
                // line 70
                yield '                        <td class="thin"></td>
                    ';
            }
            // line 72
            yield '                </thead>
                <tbody>
                    ';
            // line 74
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['sites']) || array_key_exists('sites', $context) ? $context['sites'] : (function () {
                throw new RuntimeError('Variable "sites" does not exist.', 74, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['site']) {
                // line 75
                yield '                        <tr data-id="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', [], 'any', false, false, false, 75), 'html', null, true);
                yield '" data-uid="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'uid', [], 'any', false, false, false, 75), 'html', null, true);
                yield '" data-name="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'name', [], 'any', false, false, false, 75), 'site'), 'html', null, true);
                yield '">
                            <th scope="row" data-title="';
                // line 76
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Name', 'app'), 'html', null, true);
                yield '">
                                <a href="';
                // line 77
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url(('settings/sites/'.craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', [], 'any', false, false, false, 77))), 'html', null, true);
                yield '">
                                    <span class="status ';
                // line 78
                yield (craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'enabled', [], 'any', false, false, false, 78)) ? ('enabled') : ('disabled');
                yield '"></span>';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'name', [], 'any', false, false, false, 78), 'site'), 'html', null, true);
                yield '
                                </a>
                            </th>
                            <td data-title="';
                // line 81
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Handle', 'app'), 'html', null, true);
                yield '"><code>';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'handle', [], 'any', false, false, false, 81), 'html', null, true);
                yield '</code></td>
                            <td data-title="';
                // line 82
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Language', 'app'), 'html', null, true);
                yield '"><code>';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'language', [], 'any', false, false, false, 82), 'html', null, true);
                yield '</code></td>
                            <td data-title="';
                // line 83
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Primary', 'app'), 'html', null, true);
                yield '">';
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'primary', [], 'any', false, false, false, 83)) {
                    yield '<div data-icon="check" aria-label="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Yes', 'app'), 'html', null, true);
                    yield '"></div>';
                }
                yield '</td>
                            <td data-title="';
                // line 84
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Base URL', 'app'), 'html', null, true);
                yield '"><code>';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'baseUrl', [], 'any', false, false, false, 84), 'html', null, true);
                yield '</code></td>
                            ';
                // line 85
                if (! (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                    throw new RuntimeError('Variable "group" does not exist.', 85, $this->source);
                })())) {
                    // line 86
                    yield '                                <td data-title="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Group', 'app'), 'html', null, true);
                    yield '">';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'group', [], 'any', false, false, false, 86), 'name', [], 'any', false, false, false, 86), 'site'), 'html', null, true);
                    yield '</td>
                            ';
                }
                // line 88
                yield '                            ';
                if ((isset($context['canSort']) || array_key_exists('canSort', $context) ? $context['canSort'] : (function () {
                    throw new RuntimeError('Variable "canSort" does not exist.', 88, $this->source);
                })())) {
                    // line 89
                    yield '                                <td class="thin"><a class="move icon" title="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Reorder', 'app'), 'html', null, true);
                    yield '" aria-label="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Reorder', 'app'), 'html', null, true);
                    yield '" role="button"></a></td>
                            ';
                }
                // line 91
                yield '                            ';
                if ((isset($context['multiple']) || array_key_exists('multiple', $context) ? $context['multiple'] : (function () {
                    throw new RuntimeError('Variable "multiple" does not exist.', 91, $this->source);
                })())) {
                    // line 92
                    yield '                                <td class="thin"><a class="delete icon';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'primary', [], 'any', false, false, false, 92)) {
                        yield ' disabled';
                    }
                    yield '" title="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'html', null, true);
                    yield '" aria-label="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'html', null, true);
                    yield '" role="button"></a></td>
                            ';
                }
                // line 94
                yield '                        </tr>
                    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['site'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 96
            yield '                </tbody>
            </table>
        </div>
    ';
        } else {
            // line 100
            yield '        <div class="zilch">
            <p>';
            // line 101
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('No sites exist for this group yet.', 'app'), 'html', null, true);
            yield '</p>
        </div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/sites/index.twig';
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
        return [357 => 101,  354 => 100,  348 => 96,  341 => 94,  329 => 92,  326 => 91,  318 => 89,  315 => 88,  307 => 86,  305 => 85,  299 => 84,  289 => 83,  283 => 82,  277 => 81,  269 => 78,  265 => 77,  261 => 76,  252 => 75,  248 => 74,  244 => 72,  240 => 70,  237 => 69,  233 => 67,  230 => 66,  224 => 64,  222 => 63,  218 => 62,  214 => 61,  210 => 60,  206 => 59,  202 => 58,  197 => 55,  194 => 54,  186 => 53,  179 => 49,  166 => 45,  162 => 44,  153 => 41,  151 => 40,  146 => 38,  140 => 34,  132 => 31,  130 => 29,  129 => 28,  128 => 26,  127 => 25,  126 => 24,  125 => 23,  122 => 22,  118 => 21,  108 => 20,  104 => 18,  96 => 17,  89 => 13,  87 => 11,  86 => 10,  84 => 9,  76 => 8,  70 => 1,  58 => 108,  56 => 107,  54 => 5,  52 => 4,  50 => 2,  42 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"_layouts/cp\" %}
{% set title = \"Sites\"|t('app') %}

{% set multiple = (sites|length > 1) %}
{% set canSort = group and multiple %}


{% block actionButton %}
    {{ tag('a', {
        href: url('settings/sites/new', (group ? { groupId: group.id } : null)),
        class:  ['btn', 'submit', 'add', 'icon', craft.app.sites.getRemainingSites() ? null : 'disabled'],
        text: \"New site\"|t('app'),
    }) }}
{% endblock %}


{% block sidebar %}
    <nav>
        <ul id=\"groups\">
            <li><a href=\"{{ url('settings/sites') }}\"{% if not group %} class=\"sel\"{% endif %}>{{ \"All Sites\"|t('app') }}</a></li>
            {% for g in craft.app.sites.getAllGroups() %}
                <li>
                    {{ tag('a', {
                        href: url('settings/sites', {groupId: g.id}),
                        class: group and g.id == group.id ? 'sel' : false,
                        text: g.name|t('site'),
                        data: {
                            id: g.id,
                            'raw-name': g.getName(false),
                        },
                    }) }}
                </li>
            {% endfor %}
        </ul>
    </nav>

    <div class=\"buttons\">
        <button type=\"button\" id=\"newgroupbtn\" class=\"btn add icon\">{{ \"New group\"|t('app') }}</button>

        {% if group %}
            <button type=\"button\" id=\"groupsettingsbtn\" class=\"btn settings icon menubtn\" title=\"{{ 'Settings' }}\" aria-label=\"{{ 'Settings' }}\"></button>
            <div class=\"menu\">
                <ul>
                    <li><a data-action=\"rename\" role=\"button\">{{ \"Rename selected group\"|t('app') }}</a></li>
                    <li><a data-action=\"delete\" role=\"button\"{% if sites|length %} class=\"disabled\" title=\"{{ 'You can only delete groups that have no sites.'|t('app') }}\"{% endif %}>{{ \"Delete selected group\"|t('app') }}</a></li>
                </ul>
            </div>
        {% endif %}
    </div>
{% endblock %}


{% block content %}
    {% if sites|length %}
        <div class=\"tablepane\">
            <table id=\"sites\" class=\"data fullwidth\">
                <thead>
                    <th scope=\"col\">{{ \"Name\"|t('app') }}</th>
                    <th scope=\"col\">{{ \"Handle\"|t('app') }}</th>
                    <th scope=\"col\">{{ \"Language\"|t('app') }}</th>
                    <th scope=\"col\">{{ \"Primary\"|t('app') }}</th>
                    <th scope=\"col\">{{ \"Base URL\"|t('app') }}</th>
                    {% if not group %}
                        <th scope=\"col\">{{ \"Group\"|t('app') }}</th>
                    {% endif %}
                    {% if canSort %}
                        <td class=\"thin\"></td>
                    {% endif %}
                    {% if multiple %}
                        <td class=\"thin\"></td>
                    {% endif %}
                </thead>
                <tbody>
                    {% for site in sites %}
                        <tr data-id=\"{{ site.id }}\" data-uid=\"{{ site.uid }}\" data-name=\"{{ site.name|t('site') }}\">
                            <th scope=\"row\" data-title=\"{{ 'Name'|t('app') }}\">
                                <a href=\"{{ url('settings/sites/' ~ site.id) }}\">
                                    <span class=\"status {{ site.enabled ? 'enabled' : 'disabled' }}\"></span>{{ site.name|t('site') }}
                                </a>
                            </th>
                            <td data-title=\"{{ 'Handle'|t('app') }}\"><code>{{ site.handle }}</code></td>
                            <td data-title=\"{{ 'Language'|t('app') }}\"><code>{{ site.language }}</code></td>
                            <td data-title=\"{{ 'Primary'|t('app') }}\">{% if site.primary %}<div data-icon=\"check\" aria-label=\"{{ 'Yes'|t('app') }}\"></div>{% endif %}</td>
                            <td data-title=\"{{ 'Base URL'|t('app') }}\"><code>{{ site.baseUrl }}</code></td>
                            {% if not group %}
                                <td data-title=\"{{ 'Group'|t('app') }}\">{{ site.group.name|t('site') }}</td>
                            {% endif %}
                            {% if canSort %}
                                <td class=\"thin\"><a class=\"move icon\" title=\"{{ 'Reorder'|t('app') }}\" aria-label=\"{{ 'Reorder'|t('app') }}\" role=\"button\"></a></td>
                            {% endif %}
                            {% if multiple %}
                                <td class=\"thin\"><a class=\"delete icon{% if site.primary %} disabled{% endif %}\" title=\"{{ 'Delete'|t('app') }}\" aria-label=\"{{ 'Delete'|t('app') }}\" role=\"button\"></a></td>
                            {% endif %}
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    {% else %}
        <div class=\"zilch\">
            <p>{{ 'No sites exist for this group yet.'|t('app') }}</p>
        </div>
    {% endif %}
{% endblock %}


{% js on ready %}
    new Craft.SitesAdmin();

    new Craft.SiteAdminTable({
        tableSelector: '#sites',
        minItems: 1,
        sortable: true,
        reorderAction: 'sites/reorder-sites',
        deleteAction: 'sites/delete-site',
    });
{% endjs %}
", 'settings/sites/index.twig', '/tmp/packages/craft5/src/templates/settings/sites/index.twig');
    }
}
