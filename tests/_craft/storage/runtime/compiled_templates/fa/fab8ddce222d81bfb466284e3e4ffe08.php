<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/fields */
class __TwigTemplate_307791316b835f5388866b75df77904b extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'actionButton' => $this->block_actionButton(...),
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 3
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'settings/fields');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Fields', 'app');
        // line 6
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 6, $this->source);
        })()), 'registerAssetBundle', ['craft\\web\\assets\\admintable\\AdminTableAsset'], 'method', false, false, false, 6);
        // line 8
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 8, $this->source);
        })()), 'registerTranslations', ['app', ['Handle', 'Name', 'No fields exist yet.', 'No results.', 'No usages', 'This field’s values are used as search keywords.', 'Type', 'Used by']], 'method', false, false, false, 8);
        // line 19
        $context['crumbs'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 23
        $context['emptyMessage'] = $this->extensions['craft\web\twig\Extension']->translateFilter('No fields exist yet.', 'app');
        // line 36
        ob_start();
        // line 37
        yield "  ((info) => {
    const columns = [
      { name: '__slot:title', title: Craft.t('app', 'Name'), sortField: true },
      {
        name: 'searchable',
        titleClass: 'thin',
        callback: value => {
          if (!value) {
            return null;
          }
          return `<div class=\"badge-icon\" data-icon=\"search\" title=\"\${Craft.t('app', 'This field’s values are used as search keywords.')}\" aria-label=\"\${Craft.t('app', 'This field’s values are used as search keywords.')}\" role=\"img\"></div>`;
        }
      },
    ];

    if (info.isMultiSite) {
      columns.push({
        name: 'translatable',
        titleClass: 'thin',
        callback: value => {
          if (!value) {
            return null;
          }
          return `<div class=\"badge-icon\" data-icon=\"language\" title=\"\${value}\" aria-label=\"\${value}\" role=\"img\"></div>`;
        }
      });
    }

    columns.push({
      name: '__slot:handle',
      title: Craft.t('app', 'Handle'),
      sortField: true,
    });

    columns.push({
      name: 'type',
      title: Craft.t('app', 'Type'),
      callback: (value) => {
        let label = '<div class=\"flex flex-nowrap gap-s\">' +
          `<div class=\"cp-icon small\">\${value.icon}</div>`;
        if (value.isMissing) {
          label += `<span class=\"error\">\${value.label}</span>`;
        } else {
          label += `<span>\${value.label}</span>`;
        }
        label += '</div>';
        return label;
      },
      sortField: true,
    });

    columns.push({
      name: 'usages',
      title: Craft.t('app', 'Used by'),
      callback: (value) => value || `<i class=\"light\">\${Craft.t('app', 'No usages')}</i>`,
    });

    new Craft.VueAdminTable({
      columns,
      container: '#fields-vue-admin-table',
      deleteAction: 'fields/delete-field',
      emptyMessage: info.emptyMessage,
      tableDataEndpoint: 'fields/table-data',
      search: true,
    });
  })(";
        // line 102
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(['isMultiSite' => craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 104
            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 104, $this->source);
            })()), 'app', [], 'any', false, false, false, 104), 'isMultiSite', [], 'any', false, false, false, 104), 'emptyMessage' =>         // line 105
(isset($context['emptyMessage']) || array_key_exists('emptyMessage', $context) ? $context['emptyMessage'] : (function () {
    throw new RuntimeError('Variable "emptyMessage" does not exist.', 105, $this->source);
})())]);
        // line 107
        yield ');
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/fields', 3);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/fields');
    }

    // line 26
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_actionButton(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 27
        yield '    ';
        $context['newFieldUrl'] = craft\helpers\UrlHelper::url('settings/fields/new');
        // line 28
        yield '    <a href="';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['newFieldUrl']) || array_key_exists('newFieldUrl', $context) ? $context['newFieldUrl'] : (function () {
            throw new RuntimeError('Variable "newFieldUrl" does not exist.', 28, $this->source);
        })()), 'html', null, true);
        yield '" class="submit btn add icon">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New field', 'app'), 'html', null, true);
        yield '</a>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
        yield from [];
    }

    // line 32
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 33
        yield '    <div id="fields-vue-admin-table"></div>
';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/fields';
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
        return [174 => 33,  166 => 32,  155 => 28,  152 => 27,  144 => 26,  138 => 3,  134 => 107,  132 => 105,  131 => 104,  130 => 102,  63 => 37,  61 => 36,  59 => 23,  57 => 19,  55 => 8,  53 => 6,  51 => 4,  49 => 1,  41 => 3];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% requireAdmin %}

{% extends \"_layouts/cp\" %}
{% set title = \"Fields\"|t('app') %}

{% do view.registerAssetBundle('craft\\\\web\\\\assets\\\\admintable\\\\AdminTableAsset') -%}

{% do view.registerTranslations('app', [
    'Handle',
    'Name',
    'No fields exist yet.',
    'No results.',
    'No usages',
    'This field’s values are used as search keywords.',
    'Type',
    'Used by',
]) %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% set emptyMessage = \"No fields exist yet.\"|t('app') %}


{% block actionButton %}
    {% set newFieldUrl = url('settings/fields/new') %}
    <a href=\"{{ newFieldUrl }}\" class=\"submit btn add icon\">{{ \"New field\"|t('app') }}</a>
{% endblock %}


{% block content %}
    <div id=\"fields-vue-admin-table\"></div>
{% endblock %}

{% js %}
  ((info) => {
    const columns = [
      { name: '__slot:title', title: Craft.t('app', 'Name'), sortField: true },
      {
        name: 'searchable',
        titleClass: 'thin',
        callback: value => {
          if (!value) {
            return null;
          }
          return `<div class=\"badge-icon\" data-icon=\"search\" title=\"\${Craft.t('app', 'This field’s values are used as search keywords.')}\" aria-label=\"\${Craft.t('app', 'This field’s values are used as search keywords.')}\" role=\"img\"></div>`;
        }
      },
    ];

    if (info.isMultiSite) {
      columns.push({
        name: 'translatable',
        titleClass: 'thin',
        callback: value => {
          if (!value) {
            return null;
          }
          return `<div class=\"badge-icon\" data-icon=\"language\" title=\"\${value}\" aria-label=\"\${value}\" role=\"img\"></div>`;
        }
      });
    }

    columns.push({
      name: '__slot:handle',
      title: Craft.t('app', 'Handle'),
      sortField: true,
    });

    columns.push({
      name: 'type',
      title: Craft.t('app', 'Type'),
      callback: (value) => {
        let label = '<div class=\"flex flex-nowrap gap-s\">' +
          `<div class=\"cp-icon small\">\${value.icon}</div>`;
        if (value.isMissing) {
          label += `<span class=\"error\">\${value.label}</span>`;
        } else {
          label += `<span>\${value.label}</span>`;
        }
        label += '</div>';
        return label;
      },
      sortField: true,
    });

    columns.push({
      name: 'usages',
      title: Craft.t('app', 'Used by'),
      callback: (value) => value || `<i class=\"light\">\${Craft.t('app', 'No usages')}</i>`,
    });

    new Craft.VueAdminTable({
      columns,
      container: '#fields-vue-admin-table',
      deleteAction: 'fields/delete-field',
      emptyMessage: info.emptyMessage,
      tableDataEndpoint: 'fields/table-data',
      search: true,
    });
  })({{
    {
      isMultiSite: craft.app.isMultiSite,
      emptyMessage: emptyMessage,
    }|json_encode|raw
  }});
{% endjs %}
", 'settings/fields', '/tmp/packages/craft5/src/templates/settings/fields/index.twig');
    }
}
