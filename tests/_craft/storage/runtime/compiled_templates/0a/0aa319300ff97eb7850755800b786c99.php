<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/assets/_layout */
class __TwigTemplate_389a52fcff4a5730eda414c601ae34b4 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'sidebar' => $this->block_sidebar(...),
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
        craft\helpers\Template::beginProfile('template', 'settings/assets/_layout');
        // line 2
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Asset Settings', 'app');
        // line 4
        $context['crumbs'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 8
        $context['navItems'] = ['volumes' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Volumes', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/assets')], 'transforms' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Image Transforms', 'app'), 'url' => craft\helpers\UrlHelper::url('settings/assets/transforms')]];
        // line 13
        $context['docTitle'] = ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['navItems']) || array_key_exists('navItems', $context) ? $context['navItems'] : (function () {
            throw new RuntimeError('Variable "navItems" does not exist.', 13, $this->source);
        })()), (isset($context['selectedNavItem']) || array_key_exists('selectedNavItem', $context) ? $context['selectedNavItem'] : (function () {
            throw new RuntimeError('Variable "selectedNavItem" does not exist.', 13, $this->source);
        })()), [], 'array', false, false, false, 13), 'label', [], 'any', false, false, false, 13).' - ').(isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
            throw new RuntimeError('Variable "title" does not exist.', 13, $this->source);
        })()));
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/assets/_layout', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/assets/_layout');
    }

    // line 15
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_sidebar(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 16
        yield '    ';
        yield from $this->loadTemplate('_includes/nav', 'settings/assets/_layout', 16)->unwrap()->yield(CoreExtension::toArray(['label' =>         // line 18
(isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
    throw new RuntimeError('Variable "title" does not exist.', 18, $this->source);
})()), 'items' =>         // line 19
(isset($context['navItems']) || array_key_exists('navItems', $context) ? $context['navItems'] : (function () {
    throw new RuntimeError('Variable "navItems" does not exist.', 19, $this->source);
})()), 'selectedItem' =>         // line 20
(isset($context['selectedNavItem']) || array_key_exists('selectedNavItem', $context) ? $context['selectedNavItem'] : (function () {
    throw new RuntimeError('Variable "selectedNavItem" does not exist.', 20, $this->source);
})())]));
        craft\helpers\Template::endProfile('block', 'sidebar');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/assets/_layout';
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
        return [74 => 20,  73 => 19,  72 => 18,  70 => 16,  62 => 15,  56 => 1,  54 => 13,  52 => 8,  50 => 4,  48 => 2,  40 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"_layouts/cp\" %}
{% set title = \"Asset Settings\"|t('app') %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% set navItems = {
    volumes: { label: \"Volumes\"|t('app'), url: url('settings/assets') },
    transforms: { label: \"Image Transforms\"|t('app'), url: url('settings/assets/transforms') },
} %}

{% set docTitle = navItems[selectedNavItem].label~' - '~title %}

{% block sidebar %}
    {% include \"_includes/nav\"
        with {
            label: title,
            items: navItems,
            selectedItem: selectedNavItem,
        }
        only
    %}
{% endblock %}
", 'settings/assets/_layout', '/tmp/packages/craft5/src/templates/settings/assets/_layout.twig');
    }
}
