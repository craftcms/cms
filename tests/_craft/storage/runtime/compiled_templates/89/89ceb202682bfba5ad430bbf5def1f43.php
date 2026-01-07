<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/utilities/Migrations.twig */
class __TwigTemplate_6a937ac8e957e7458fd93b2538b2e6ce extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/utilities/Migrations.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/Migrations.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 3
        if (! (isset($context['newMigrations']) || array_key_exists('newMigrations', $context) ? $context['newMigrations'] : (function () {
            throw new RuntimeError('Variable "newMigrations" does not exist.', 3, $this->source);
        })())) {
            // line 4
            echo '    <p class="zilch">';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No pending content migrations.', 'app'), 'html', null, true);
            echo '</p>
';
        }
        // line 6
        echo '
';
        // line 7
        if (((isset($context['newMigrations']) || array_key_exists('newMigrations', $context) ? $context['newMigrations'] : (function () {
            throw new RuntimeError('Variable "newMigrations" does not exist.', 7, $this->source);
        })()) || (isset($context['migrationHistory']) || array_key_exists('migrationHistory', $context) ? $context['migrationHistory'] : (function () {
            throw new RuntimeError('Variable "migrationHistory" does not exist.', 7, $this->source);
        })()))) {
            // line 8
            echo '    ';
            if ((isset($context['newMigrations']) || array_key_exists('newMigrations', $context) ? $context['newMigrations'] : (function () {
                throw new RuntimeError('Variable "newMigrations" does not exist.', 8, $this->source);
            })())) {
                // line 9
                echo '        <form method="post" accept-charset="UTF-8" action="" class="buttons">
            ';
                // line 10
                echo craft\helpers\Html::csrfInput();
                echo '
            ';
                // line 11
                echo craft\helpers\Html::actionInput('utilities/apply-new-migrations');
                echo '
            <button type="submit" class="btn submit">';
                // line 12
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Apply new migrations', 'app'), 'html', null, true);
                echo '</button>
        </form>
    ';
            }
            // line 15
            echo '
    <table class="data fullwidth">
        <thead>
        <tr>
            <th>';
            // line 19
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Name', 'app'), 'html', null, true);
            echo '</th>
            <th>';
            // line 20
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Status', 'app'), 'html', null, true);
            echo '</th>
            <th>';
            // line 21
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Apply Time', 'app'), 'html', null, true);
            echo '</th>
        </tr>
        </thead>
        <tbody>

            ';
            // line 26
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['newMigrations']) || array_key_exists('newMigrations', $context) ? $context['newMigrations'] : (function () {
                throw new RuntimeError('Variable "newMigrations" does not exist.', 26, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['newMigration']) {
                // line 27
                echo '                <tr>
                    <td>';
                // line 28
                echo twig_escape_filter($this->env, $context['newMigration'], 'html', null, true);
                echo '</td>
                    <td>';
                // line 29
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New', 'app'), 'html', null, true);
                echo '</td>
                    <td></td>
                </tr>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['newMigration'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 33
            echo '
            ';
            // line 34
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['migrationHistory']) || array_key_exists('migrationHistory', $context) ? $context['migrationHistory'] : (function () {
                throw new RuntimeError('Variable "migrationHistory" does not exist.', 34, $this->source);
            })()));
            foreach ($context['_seq'] as $context['migrationName'] => $context['migration']) {
                // line 35
                echo '                <tr>
                    <td>';
                // line 36
                echo twig_escape_filter($this->env, $context['migrationName'], 'html', null, true);
                echo '</td>
                    <td>';
                // line 37
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Applied', 'app'), 'html', null, true);
                echo '</td>
                    <td>';
                // line 38
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->datetimeFilter($this->env, $context['migration']), 'html', null, true);
                echo '</td>
                </tr>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['migrationName'], $context['migration'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 41
            echo '        </tbody>
    </table>
';
        }
        craft\helpers\Template::endProfile('template', '_components/utilities/Migrations.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/Migrations.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [146 => 41,  137 => 38,  133 => 37,  129 => 36,  126 => 35,  122 => 34,  119 => 33,  109 => 29,  105 => 28,  102 => 27,  98 => 26,  90 => 21,  86 => 20,  82 => 19,  76 => 15,  70 => 12,  66 => 11,  62 => 10,  59 => 9,  56 => 8,  54 => 7,  51 => 6,  45 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import \"_includes/forms\" as forms %}

{% if not newMigrations %}
    <p class=\"zilch\">{{ 'No pending content migrations.'|t('app') }}</p>
{% endif %}

{% if newMigrations or migrationHistory %}
    {% if newMigrations %}
        <form method=\"post\" accept-charset=\"UTF-8\" action=\"\" class=\"buttons\">
            {{ csrfInput() }}
            {{ actionInput('utilities/apply-new-migrations') }}
            <button type=\"submit\" class=\"btn submit\">{{ 'Apply new migrations'|t('app') }}</button>
        </form>
    {% endif %}

    <table class=\"data fullwidth\">
        <thead>
        <tr>
            <th>{{ 'Name'|t('app') }}</th>
            <th>{{ 'Status'|t('app') }}</th>
            <th>{{ 'Apply Time'|t('app') }}</th>
        </tr>
        </thead>
        <tbody>

            {% for newMigration in newMigrations %}
                <tr>
                    <td>{{ newMigration }}</td>
                    <td>{{ 'New'|t('app') }}</td>
                    <td></td>
                </tr>
            {% endfor %}

            {% for migrationName, migration in migrationHistory %}
                <tr>
                    <td>{{ migrationName }}</td>
                    <td>{{ 'Applied'|t('app') }}</td>
                    <td>{{ migration|datetime() }}</td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
{% endif %}
", '_components/utilities/Migrations.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/Migrations.twig');
    }
}
