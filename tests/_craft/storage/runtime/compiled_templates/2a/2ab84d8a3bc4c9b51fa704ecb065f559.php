<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/utilities/Migrations.twig */
class __TwigTemplate_8d8b97058fead6daac004528545e93e7 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/Migrations.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/Migrations.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 3
        if (! (isset($context['newMigrations']) || array_key_exists('newMigrations', $context) ? $context['newMigrations'] : (function () {
            throw new RuntimeError('Variable "newMigrations" does not exist.', 3, $this->source);
        })())) {
            // line 4
            yield '    <p class="zilch">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('No pending content migrations.', 'app'), 'html', null, true);
            yield '</p>
';
        }
        // line 6
        yield '
';
        // line 7
        if (((isset($context['newMigrations']) || array_key_exists('newMigrations', $context) ? $context['newMigrations'] : (function () {
            throw new RuntimeError('Variable "newMigrations" does not exist.', 7, $this->source);
        })()) || (isset($context['migrationHistory']) || array_key_exists('migrationHistory', $context) ? $context['migrationHistory'] : (function () {
            throw new RuntimeError('Variable "migrationHistory" does not exist.', 7, $this->source);
        })()))) {
            // line 8
            yield '    ';
            if ((isset($context['newMigrations']) || array_key_exists('newMigrations', $context) ? $context['newMigrations'] : (function () {
                throw new RuntimeError('Variable "newMigrations" does not exist.', 8, $this->source);
            })())) {
                // line 9
                yield '        <form method="post" accept-charset="UTF-8" action="" class="buttons">
            ';
                // line 10
                yield craft\helpers\Html::csrfInput();
                yield '
            ';
                // line 11
                yield craft\helpers\Html::actionInput('utilities/apply-new-migrations');
                yield '
            <button type="submit" class="btn submit">';
                // line 12
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Apply new migrations', 'app'), 'html', null, true);
                yield '</button>
        </form>
    ';
            }
            // line 15
            yield '
    <table class="data fullwidth">
        <thead>
        <tr>
            <th>';
            // line 19
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Name', 'app'), 'html', null, true);
            yield '</th>
            <th>';
            // line 20
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Status', 'app'), 'html', null, true);
            yield '</th>
            <th>';
            // line 21
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Apply Time', 'app'), 'html', null, true);
            yield '</th>
        </tr>
        </thead>
        <tbody>

            ';
            // line 26
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['newMigrations']) || array_key_exists('newMigrations', $context) ? $context['newMigrations'] : (function () {
                throw new RuntimeError('Variable "newMigrations" does not exist.', 26, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['newMigration']) {
                // line 27
                yield '                <tr>
                    <td>';
                // line 28
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['newMigration'], 'html', null, true);
                yield '</td>
                    <td>';
                // line 29
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New', 'app'), 'html', null, true);
                yield '</td>
                    <td></td>
                </tr>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['newMigration'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 33
            yield '
            ';
            // line 34
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['migrationHistory']) || array_key_exists('migrationHistory', $context) ? $context['migrationHistory'] : (function () {
                throw new RuntimeError('Variable "migrationHistory" does not exist.', 34, $this->source);
            })()));
            foreach ($context['_seq'] as $context['migrationName'] => $context['migration']) {
                // line 35
                yield '                <tr>
                    <td>';
                // line 36
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['migrationName'], 'html', null, true);
                yield '</td>
                    <td>';
                // line 37
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Applied', 'app'), 'html', null, true);
                yield '</td>
                    <td>';
                // line 38
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->datetimeFilter($this->env, $context['migration']), 'html', null, true);
                yield '</td>
                </tr>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['migrationName'], $context['migration'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 41
            yield '        </tbody>
    </table>
';
        }
        craft\helpers\Template::endProfile('template', '_components/utilities/Migrations.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/Migrations.twig';
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
        return [151 => 41,  142 => 38,  138 => 37,  134 => 36,  131 => 35,  127 => 34,  124 => 33,  114 => 29,  110 => 28,  107 => 27,  103 => 26,  95 => 21,  91 => 20,  87 => 19,  81 => 15,  75 => 12,  71 => 11,  67 => 10,  64 => 9,  61 => 8,  59 => 7,  56 => 6,  50 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
", '_components/utilities/Migrations.twig', '/tmp/packages/craft5/src/templates/_components/utilities/Migrations.twig');
    }
}
