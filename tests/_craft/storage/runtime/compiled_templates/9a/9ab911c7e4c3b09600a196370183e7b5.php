<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/sites/index.twig */
class __TwigTemplate_6b828c8e0a9675236a764dfda513c772 extends Template
{
    private $source;

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
    protected function doGetParent(array $context)
    {
        // line 1
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = [])
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
        // line 104
        ob_start();
        // line 105
        echo "    new Craft.SitesAdmin();

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
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/sites/index.twig');
    }

    // line 8
    public function block_actionButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 9
        echo '    ';
        $context['newSiteUrl'] = craft\helpers\UrlHelper::url('settings/sites/new', (((isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
            throw new RuntimeError('Variable "group" does not exist.', 9, $this->source);
        })())) ? (['groupId' => craft\helpers\Template::attribute($this->env, $this->source, (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
            throw new RuntimeError('Variable "group" does not exist.', 9, $this->source);
        })()), 'id', [])]) : (null)));
        // line 10
        echo '    <a href="';
        echo twig_escape_filter($this->env, (isset($context['newSiteUrl']) || array_key_exists('newSiteUrl', $context) ? $context['newSiteUrl'] : (function () {
            throw new RuntimeError('Variable "newSiteUrl" does not exist.', 10, $this->source);
        })()), 'html', null, true);
        echo '" class="btn submit add icon">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New site', 'app'), 'html', null, true);
        echo '</a>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
    }

    // line 14
    public function block_sidebar($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 15
        echo '    <nav>
        <ul id="groups">
            <li><a href="';
        // line 17
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('settings/sites'), 'html', null, true);
        echo '"';
        if (! (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
            throw new RuntimeError('Variable "group" does not exist.', 17, $this->source);
        })())) {
            echo ' class="sel"';
        }
        echo '>';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('All Sites', 'app'), 'html', null, true);
        echo '</a></li>
            ';
        // line 18
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['allGroups']) || array_key_exists('allGroups', $context) ? $context['allGroups'] : (function () {
            throw new RuntimeError('Variable "allGroups" does not exist.', 18, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['g']) {
            // line 19
            echo '                <li>
                    ';
            // line 20
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\UrlHelper::url('settings/sites', ['groupId' => craft\helpers\Template::attribute($this->env, $this->source,             // line 21
                $context['g'], 'id', [])]), 'class' => (((            // line 22
                    (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                        throw new RuntimeError('Variable "group" does not exist.', 22, $this->source);
                    })()) && (craft\helpers\Template::attribute($this->env, $this->source, $context['g'], 'id', []) == craft\helpers\Template::attribute($this->env, $this->source, (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                        throw new RuntimeError('Variable "group" does not exist.', 22, $this->source);
                    })()), 'id', [])))) ? ('sel') : (false)), 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 23
                        $context['g'], 'name', []), 'site'), 'data' => ['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 25
                            $context['g'], 'id', []), 'raw-name' => craft\helpers\Template::attribute($this->env, $this->source,             // line 26
                                $context['g'], 'getName', [0 => false], 'method')]]);
            // line 28
            echo '
                </li>
            ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['g'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 31
        echo '        </ul>
    </nav>

    <div class="buttons">
        <button type="button" id="newgroupbtn" class="btn add icon">';
        // line 35
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New group', 'app'), 'html', null, true);
        echo '</button>

        ';
        // line 37
        if ((isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
            throw new RuntimeError('Variable "group" does not exist.', 37, $this->source);
        })())) {
            // line 38
            echo '            <button type="button" id="groupsettingsbtn" class="btn settings icon menubtn" title="';
            echo 'Settings';
            echo '" aria-label="';
            echo 'Settings';
            echo '"></button>
            <div class="menu">
                <ul>
                    <li><a data-action="rename" role="button">';
            // line 41
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Rename selected group', 'app'), 'html', null, true);
            echo '</a></li>
                    <li><a data-action="delete" role="button"';
            // line 42
            if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['sites']) || array_key_exists('sites', $context) ? $context['sites'] : (function () {
                throw new RuntimeError('Variable "sites" does not exist.', 42, $this->source);
            })()))) {
                echo ' class="disabled" title="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('You can only delete groups that have no sites.', 'app'), 'html', null, true);
                echo '"';
            }
            echo '>';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Delete selected group', 'app'), 'html', null, true);
            echo '</a></li>
                </ul>
            </div>
        ';
        }
        // line 46
        echo '    </div>
';
        craft\helpers\Template::endProfile('block', 'sidebar');
    }

    // line 50
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 51
        echo '    ';
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['sites']) || array_key_exists('sites', $context) ? $context['sites'] : (function () {
            throw new RuntimeError('Variable "sites" does not exist.', 51, $this->source);
        })()))) {
            // line 52
            echo '        <div class="tablepane">
            <table id="sites" class="data fullwidth">
                <thead>
                    <th scope="col">';
            // line 55
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Name', 'app'), 'html', null, true);
            echo '</th>
                    <th scope="col">';
            // line 56
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Handle', 'app'), 'html', null, true);
            echo '</th>
                    <th scope="col">';
            // line 57
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Language', 'app'), 'html', null, true);
            echo '</th>
                    <th scope="col">';
            // line 58
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Primary', 'app'), 'html', null, true);
            echo '</th>
                    <th scope="col">';
            // line 59
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Base URL', 'app'), 'html', null, true);
            echo '</th>
                    ';
            // line 60
            if (! (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                throw new RuntimeError('Variable "group" does not exist.', 60, $this->source);
            })())) {
                // line 61
                echo '                        <th scope="col">';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Group', 'app'), 'html', null, true);
                echo '</th>
                    ';
            }
            // line 63
            echo '                    ';
            if ((isset($context['canSort']) || array_key_exists('canSort', $context) ? $context['canSort'] : (function () {
                throw new RuntimeError('Variable "canSort" does not exist.', 63, $this->source);
            })())) {
                // line 64
                echo '                        <td class="thin"></td>
                    ';
            }
            // line 66
            echo '                    ';
            if ((isset($context['multiple']) || array_key_exists('multiple', $context) ? $context['multiple'] : (function () {
                throw new RuntimeError('Variable "multiple" does not exist.', 66, $this->source);
            })())) {
                // line 67
                echo '                        <td class="thin"></td>
                    ';
            }
            // line 69
            echo '                </thead>
                <tbody>
                    ';
            // line 71
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['sites']) || array_key_exists('sites', $context) ? $context['sites'] : (function () {
                throw new RuntimeError('Variable "sites" does not exist.', 71, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['site']) {
                // line 72
                echo '                        <tr data-id="';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', []), 'html', null, true);
                echo '" data-uid="';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'uid', []), 'html', null, true);
                echo '" data-name="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'name', []), 'site'), 'html', null, true);
                echo '">
                            <th scope="row" data-title="';
                // line 73
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Name', 'app'), 'html', null, true);
                echo '">
                                <a href="';
                // line 74
                echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url(('settings/sites/'.craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', []))), 'html', null, true);
                echo '">
                                    <span class="status ';
                // line 75
                echo (craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'enabled', [])) ? ('enabled') : ('disabled');
                echo '"></span>';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'name', []), 'site'), 'html', null, true);
                echo '
                                </a>
                            </th>
                            <td data-title="';
                // line 78
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Handle', 'app'), 'html', null, true);
                echo '"><code>';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'handle', []), 'html', null, true);
                echo '</code></td>
                            <td data-title="';
                // line 79
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Language', 'app'), 'html', null, true);
                echo '"><code>';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'language', []), 'html', null, true);
                echo '</code></td>
                            <td data-title="';
                // line 80
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Primary', 'app'), 'html', null, true);
                echo '">';
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'primary', [])) {
                    echo '<div data-icon="check" aria-label="';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Yes', 'app'), 'html', null, true);
                    echo '"></div>';
                }
                echo '</td>
                            <td data-title="';
                // line 81
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Base URL', 'app'), 'html', null, true);
                echo '"><code>';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'baseUrl', []), 'html', null, true);
                echo '</code></td>
                            ';
                // line 82
                if (! (isset($context['group']) || array_key_exists('group', $context) ? $context['group'] : (function () {
                    throw new RuntimeError('Variable "group" does not exist.', 82, $this->source);
                })())) {
                    // line 83
                    echo '                                <td data-title="';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Group', 'app'), 'html', null, true);
                    echo '">';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'group', []), 'name', []), 'site'), 'html', null, true);
                    echo '</td>
                            ';
                }
                // line 85
                echo '                            ';
                if ((isset($context['canSort']) || array_key_exists('canSort', $context) ? $context['canSort'] : (function () {
                    throw new RuntimeError('Variable "canSort" does not exist.', 85, $this->source);
                })())) {
                    // line 86
                    echo '                                <td class="thin"><a class="move icon" title="';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Reorder', 'app'), 'html', null, true);
                    echo '" aria-label="';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Reorder', 'app'), 'html', null, true);
                    echo '" role="button"></a></td>
                            ';
                }
                // line 88
                echo '                            ';
                if ((isset($context['multiple']) || array_key_exists('multiple', $context) ? $context['multiple'] : (function () {
                    throw new RuntimeError('Variable "multiple" does not exist.', 88, $this->source);
                })())) {
                    // line 89
                    echo '                                <td class="thin"><a class="delete icon';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'primary', [])) {
                        echo ' disabled';
                    }
                    echo '" title="';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'html', null, true);
                    echo '" aria-label="';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'html', null, true);
                    echo '" role="button"></a></td>
                            ';
                }
                // line 91
                echo '                        </tr>
                    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['site'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 93
            echo '                </tbody>
            </table>
        </div>
    ';
        } else {
            // line 97
            echo '        <div class="zilch">
            <p>';
            // line 98
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No sites exist for this group yet.', 'app'), 'html', null, true);
            echo '</p>
        </div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/sites/index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [343 => 98,  340 => 97,  334 => 93,  327 => 91,  315 => 89,  312 => 88,  304 => 86,  301 => 85,  293 => 83,  291 => 82,  285 => 81,  275 => 80,  269 => 79,  263 => 78,  255 => 75,  251 => 74,  247 => 73,  238 => 72,  234 => 71,  230 => 69,  226 => 67,  223 => 66,  219 => 64,  216 => 63,  210 => 61,  208 => 60,  204 => 59,  200 => 58,  196 => 57,  192 => 56,  188 => 55,  183 => 52,  180 => 51,  175 => 50,  169 => 46,  156 => 42,  152 => 41,  143 => 38,  141 => 37,  136 => 35,  130 => 31,  122 => 28,  120 => 26,  119 => 25,  118 => 23,  117 => 22,  116 => 21,  115 => 20,  112 => 19,  108 => 18,  98 => 17,  94 => 15,  89 => 14,  79 => 10,  76 => 9,  71 => 8,  65 => 1,  53 => 105,  51 => 104,  49 => 5,  47 => 4,  45 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"_layouts/cp\" %}
{% set title = \"Sites\"|t('app') %}

{% set multiple = (sites|length > 1) %}
{% set canSort = group and multiple %}


{% block actionButton %}
    {% set newSiteUrl = url('settings/sites/new', (group ? { groupId: group.id } : null)) %}
    <a href=\"{{ newSiteUrl }}\" class=\"btn submit add icon\">{{ \"New site\"|t('app') }}</a>
{% endblock %}


{% block sidebar %}
    <nav>
        <ul id=\"groups\">
            <li><a href=\"{{ url('settings/sites') }}\"{% if not group %} class=\"sel\"{% endif %}>{{ \"All Sites\"|t('app') }}</a></li>
            {% for g in allGroups %}
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
", 'settings/sites/index.twig', '/Users/brianhanson/Development/craft5/src/templates/settings/sites/index.twig');
    }
}
