<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/plugins */
class __TwigTemplate_c2eb738e207a409e83d27f1be05bec88 extends Template
{
    private readonly Source $source;

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
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'settings/plugins');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Plugins', 'app');
        // line 5
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 5, $this->source);
        })()), 'registerAssetBundle', ['craft\\web\\assets\\plugins\\PluginsAsset'], 'method', false, false, false, 5);
        // line 7
        $context['crumbs'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 12
        $context['info'] = $this->extensions['craft\web\twig\Extension']->multisortFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 12, $this->source);
        })()), 'app', [], 'any', false, false, false, 12), 'plugins', [], 'any', false, false, false, 12), 'getAllPluginInfo', [], 'method', false, false, false, 12), ['isEnabled', 'isInstalled', 'name'], [        // line 14
            (isset($context['SORT_DESC']) || array_key_exists('SORT_DESC', $context) ? $context['SORT_DESC'] : (function () {
                throw new RuntimeError('Variable "SORT_DESC" does not exist.', 14, $this->source);
            })()), (isset($context['SORT_DESC']) || array_key_exists('SORT_DESC', $context) ? $context['SORT_DESC'] : (function () {
                throw new RuntimeError('Variable "SORT_DESC" does not exist.', 14, $this->source);
            })()), (isset($context['SORT_ASC']) || array_key_exists('SORT_ASC', $context) ? $context['SORT_ASC'] : (function () {
                throw new RuntimeError('Variable "SORT_ASC" does not exist.', 14, $this->source);
            })())], [        // line 15
                (isset($context['SORT_NUMERIC']) || array_key_exists('SORT_NUMERIC', $context) ? $context['SORT_NUMERIC'] : (function () {
                    throw new RuntimeError('Variable "SORT_NUMERIC" does not exist.', 15, $this->source);
                })()), (isset($context['SORT_NUMERIC']) || array_key_exists('SORT_NUMERIC', $context) ? $context['SORT_NUMERIC'] : (function () {
                    throw new RuntimeError('Variable "SORT_NUMERIC" does not exist.', 15, $this->source);
                })()), (isset($context['SORT_NATURAL']) || array_key_exists('SORT_NATURAL', $context) ? $context['SORT_NATURAL'] : (function () {
                    throw new RuntimeError('Variable "SORT_NATURAL" does not exist.', 15, $this->source);
                })())]);
        // line 18
        $context['disabledPlugins'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 18, $this->source);
        })()), 'app', [], 'any', false, false, false, 18), 'config', [], 'any', false, false, false, 18), 'general', [], 'any', false, false, false, 18), 'disabledPlugins', [], 'any', false, false, false, 18);
        // line 184
        ob_start();
        // line 185
        yield 'new Craft.PluginManager();
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/plugins', 3);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/plugins');
    }

    // line 21
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 22
        yield '    ';
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['info']) || array_key_exists('info', $context) ? $context['info'] : (function () {
            throw new RuntimeError('Variable "info" does not exist.', 22, $this->source);
        })()))) {
            // line 23
            yield '        <div class="tablepane">
            <table id="plugins" class="data fullwidth">
                <tbody>
                    ';
            // line 26
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['info']) || array_key_exists('info', $context) ? $context['info'] : (function () {
                throw new RuntimeError('Variable "info" does not exist.', 26, $this->source);
            })()));
            foreach ($context['_seq'] as $context['handle'] => $context['config']) {
                // line 27
                yield '                        ';
                $context['pluginStoreUrl'] = craft\helpers\UrlHelper::url(('plugin-store/'.$context['handle']));
                // line 28
                yield '                        ';
                $context['forceDisabled'] = (((isset($context['disabledPlugins']) || array_key_exists('disabledPlugins', $context) ? $context['disabledPlugins'] : (function () {
                    throw new RuntimeError('Variable "disabledPlugins" does not exist.', 28, $this->source);
                })()) == '*') || CoreExtension::inFilter($context['handle'], (isset($context['disabledPlugins']) || array_key_exists('disabledPlugins', $context) ? $context['disabledPlugins'] : (function () {
                    throw new RuntimeError('Variable "disabledPlugins" does not exist.', 28, $this->source);
                })())));
                // line 29
                yield '                        <tr id="plugin-';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['handle'], 'html', null, true);
                yield '" data-name="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [], 'any', false, false, false, 29), 'html', null, true);
                yield '" data-handle="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['handle'], 'html', null, true);
                yield '">
                            <th>
                                <div class="plugin-infos">
                                    <a class="icon" href="';
                // line 32
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['pluginStoreUrl']) || array_key_exists('pluginStoreUrl', $context) ? $context['pluginStoreUrl'] : (function () {
                    throw new RuntimeError('Variable "pluginStoreUrl" does not exist.', 32, $this->source);
                })()), 'html', null, true);
                yield '" title="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('View {plugin} in the Plugin Store', 'app', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [], 'any', false, false, false, 32)]), 'html', null, true);
                yield '" title="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('View {plugin} in the Plugin Store', 'app', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [], 'any', false, false, false, 32)]), 'html', null, true);
                yield '">
                                        ';
                // line 33
                yield $this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 33, $this->source);
                })()), 'app', [], 'any', false, false, false, 33), 'plugins', [], 'any', false, false, false, 33), 'getPluginIconSvg', [$context['handle']], 'method', false, false, false, 33), true, true);
                yield '
                                        ';
                // line 34
                if (((craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKeyStatus', [], 'any', false, false, false, 34) == 'valid') || ! Twig\Extension\CoreExtension::testEmpty(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseIssues', [], 'any', false, false, false, 34)))) {
                    // line 35
                    yield '                                            <span class="license-key-status ';
                    yield (Twig\Extension\CoreExtension::testEmpty(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseIssues', [], 'any', false, false, false, 35))) ? ('valid') : ('');
                    yield '"></span>
                                        ';
                }
                // line 37
                yield '                                    </a>
                                    <div class="plugin-details">
                                        <div class="plugin-id">
                                            <h2>';
                // line 40
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [], 'any', false, false, false, 40), 'html', null, true);
                yield '</h2>
                                            ';
                // line 41
                if ((craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'hasMultipleEditions', [], 'any', false, false, false, 41) || craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isTrial', [], 'any', false, false, false, 41))) {
                    // line 42
                    yield '                                                ';
                    ob_start();
                    // line 46
                    yield '                                                    ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'hasMultipleEditions', [], 'any', false, false, false, 46)) {
                        yield '<div class="edition-name">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'edition', [], 'any', false, false, false, 46), 'html', null, true);
                        yield '</div>';
                    }
                    // line 47
                    yield '                                                    ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isTrial', [], 'any', false, false, false, 47)) {
                        yield '<div class="edition-trial">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Trial', 'app'), 'html', null, true);
                        yield '</div>';
                    }
                    // line 48
                    yield '                                                ';
                    echo craft\helpers\Html::tag(((craft\helpers\Template::attribute($this->env, $this->source,                     // line 42
                        $context['config'], 'upgradeAvailable', [], 'any', false, false, false, 42)) ? ('a') : ('div')), ob_get_clean(), ['class' => 'edition', 'href' => ((craft\helpers\Template::attribute($this->env, $this->source,                     // line 44
                            $context['config'], 'upgradeAvailable', [], 'any', false, false, false, 44)) ? ((isset($context['pluginStoreUrl']) || array_key_exists('pluginStoreUrl', $context) ? $context['pluginStoreUrl'] : (function () {
                                throw new RuntimeError('Variable "pluginStoreUrl" does not exist.', 44, $this->source);
                            })())) : (false))]);
                    // line 49
                    yield '                                            ';
                }
                // line 50
                yield '                                            <span class="version">';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'version', [], 'any', false, false, false, 50), 'html', null, true);
                yield '</span>
                                        </div>
                                        ';
                // line 52
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'description', [], 'any', false, false, false, 52)) {
                    // line 53
                    yield '                                            <p>';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'description', [], 'any', false, false, false, 53), 'html', null, true);
                    yield '</p>
                                        ';
                }
                // line 55
                yield '                                        ';
                if (((craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'developer', [], 'any', false, false, false, 55) || craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'documentationUrl', [], 'any', false, false, false, 55)) || craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'hasCpSettings', [], 'any', false, false, false, 55))) {
                    // line 56
                    yield '                                            <ul class="links">';
                    // line 57
                    $___internal_parse_0_ = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
                        // line 58
                        yield '                                                    ';
                        if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'developer', [], 'any', false, false, false, 58)) {
                            // line 59
                            yield '                                                        <li class="link-developer">
                                                            ';
                            // line 60
                            if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'developerUrl', [], 'any', false, false, false, 60)) {
                                // line 61
                                yield '                                                                ';
                                yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\Template::attribute($this->env, $this->source,                                 // line 62
                                    $context['config'], 'developerUrl', [], 'any', false, false, false, 62), 'rel' => 'noopener', 'target' => '_blank', 'text' => craft\helpers\Template::attribute($this->env, $this->source,                                 // line 65
                                        $context['config'], 'developer', [], 'any', false, false, false, 65)]);
                                // line 66
                                yield '
                                                            ';
                            } else {
                                // line 68
                                yield '                                                                <span>';
                                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'developer', [], 'any', false, false, false, 68), 'html', null, true);
                                yield '</span>
                                                            ';
                            }
                            // line 70
                            yield '                                                        </li>
                                                    ';
                        }
                        // line 72
                        yield '                                                    ';
                        if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'documentationUrl', [], 'any', false, false, false, 72)) {
                            // line 73
                            yield '                                                        <li class="link-docs">
                                                            ';
                            // line 74
                            yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\Template::attribute($this->env, $this->source,                             // line 75
                                $context['config'], 'documentationUrl', [], 'any', false, false, false, 75), 'rel' => 'noopener', 'target' => '_blank', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Documentation', 'app')]);
                            // line 79
                            yield '
                                                        </li>
                                                    ';
                        }
                        // line 82
                        yield '                                                    ';
                        if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'hasCpSettings', [], 'any', false, false, false, 82)) {
                            // line 83
                            yield '                                                        <li class="link-settings">
                                                            ';
                            // line 84
                            yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\UrlHelper::url(('settings/plugins/'.craft\helpers\Template::attribute($this->env, $this->source,                             // line 85
                                $context['config'], 'moduleId', [], 'any', false, false, false, 85))), 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app')]);
                            // line 87
                            yield '
                                                        </li>
                                                    ';
                        }
                        // line 90
                        yield '                                                ';
                        yield from [];
                    })())) ? '' : new Markup($tmp, $this->env->getCharset());
                    // line 57
                    yield Twig\Extension\CoreExtension::spaceless($___internal_parse_0_);
                    // line 91
                    yield '</ul>
                                        ';
                }
                // line 93
                yield '                                        ';
                $context['showLicenseKey'] = (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKey', [], 'any', false, false, false, 93) || (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKeyStatus', [], 'any', false, false, false, 93) != 'unknown'));
                // line 94
                yield '                                        <div class="flex license-key';
                if (! (isset($context['showLicenseKey']) || array_key_exists('showLicenseKey', $context) ? $context['showLicenseKey'] : (function () {
                    throw new RuntimeError('Variable "showLicenseKey" does not exist.', 94, $this->source);
                })())) {
                    yield ' hidden';
                }
                yield '">
                                            <div class="pane">
                                                <input class="text code';
                // line 96
                if (! Twig\Extension\CoreExtension::testEmpty(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseIssues', [], 'any', false, false, false, 96))) {
                    yield ' error';
                }
                yield '" size="29" value="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((((Twig\Extension\CoreExtension::slice($this->env->getCharset(), craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKey', [], 'any', false, false, false, 96), 0, 1) == '$')) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKey', [], 'any', false, false, false, 96)) : (Twig\Extension\CoreExtension::trim($this->extensions['craft\web\twig\Extension']->replaceFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKey', [], 'any', false, false, false, 96), '/.{4}/', '$0-'), '-'))), 'html', null, true);
                yield '" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX-XXXX" readonly>
                                            </div>
                                            ';
                // line 98
                yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Buy now', 'app'), 'class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['btn', ((! Twig\Extension\CoreExtension::testEmpty(craft\helpers\Template::attribute($this->env, $this->source,                 // line 102
                    $context['config'], 'licenseIssues', [], 'any', false, false, false, 102))) ? ('submit') : ('')), (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 103
                        $context['config'], 'licenseKeyStatus', [], 'any', false, false, false, 103) != 'trial')) ? ('hidden') : (''))]), 'href' => craft\helpers\UrlHelper::url(((('plugin-store/buy/'.                 // line 105
                        $context['handle']).'/').craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'edition', [], 'any', false, false, false, 105)))]);
                // line 106
                yield '
                                            <div class="spinner hidden"></div>
                                        </div>
                                        ';
                // line 109
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseIssues', [], 'any', false, false, false, 109));
                foreach ($context['_seq'] as $context['_key'] => $context['issue']) {
                    // line 110
                    yield '                                            <p class="error">
                                                ';
                    // line 111
                    switch ($context['issue']) {
                        case 'wrong_edition':
                            // line 113
                            yield '                                                        ';
                            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('This license is for the {name} edition.', 'app', ['name' => $this->extensions['craft\web\twig\Extension']->ucfirstFilter(craft\helpers\Template::attribute($this->env, $this->source,                             // line 114
                                $context['config'], 'licensedEdition', [], 'any', false, false, false, 114))]), 'html', null, true);
                            // line 115
                            yield '
                                                    ';
                            break;
                        case 'no_trials':
                            // line 117
                            yield '                                                        ';
                            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Plugin trials are not allowed on this domain.', 'app'), 'html', null, true);
                            yield '
                                                    ';
                            break;
                        case 'mismatched':
                            // line 119
                            yield '                                                        ';
                            yield $this->extensions['craft\web\twig\Extension']->translateFilter('This license is tied to another Craft install. Visit {accountLink} to detach it, or <a href="{buyUrl}">buy a new license</a>.', 'app', ['accountLink' => '<a href="https://console.craftcms.com" rel="noopener" target="_blank">console.craftcms.com</a>', 'buyUrl' => craft\helpers\UrlHelper::url(((('plugin-store/buy/'.                             // line 121
                $context['handle']).'/').craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'edition', [], 'any', false, false, false, 121)))]);
                            // line 122
                            yield '
                                                    ';
                            break;
                        case 'astray':
                            // line 124
                            yield '                                                        ';
                            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('This license isn’t allowed to run version {version}.', 'app', ['version' => craft\helpers\Template::attribute($this->env, $this->source,                             // line 125
                                $context['config'], 'version', [], 'any', false, false, false, 125)]), 'html', null, true);
                            // line 126
                            yield '
                                                    ';
                            break;
                        case 'required':
                            // line 128
                            yield '                                                        ';
                            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('A license key is required.', 'app'), 'html', null, true);
                            yield '
                                                    ';
                            break;
                        default:
                            // line 130
                            yield '                                                        ';
                            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Your license key is invalid.', 'app'), 'html', null, true);
                            yield '
                                                ';
                    }
                    // line 132
                    yield '                                            </p>
                                        ';
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['issue'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 134
                yield '                                    </div>
                                </div>
                            </th>
                            <td class="nowrap" data-title="';
                // line 137
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Status', 'app'), 'html', null, true);
                yield '">
                                ';
                // line 138
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isEnabled', [], 'any', false, false, false, 138)) {
                    // line 139
                    yield '                                    <span class="status on"></span>';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Installed', 'app'), 'html', null, true);
                    yield '
                                ';
                } elseif (craft\helpers\Template::attribute($this->env, $this->source,                 // line 140
                    $context['config'], 'isInstalled', [], 'any', false, false, false, 140)) {
                    // line 141
                    yield '                                    <span class="status off"></span>';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Disabled', 'app'), 'html', null, true);
                    yield '
                                ';
                } else {
                    // line 143
                    yield '                                    <span class="status"></span><span class="light">';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Not installed', 'app'), 'html', null, true);
                    yield '</span>
                                ';
                }
                // line 145
                yield '                            </td>
                            <td class="nowrap thin" data-title="';
                // line 146
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Action', 'app'), 'html', null, true);
                yield '">
                                <form method="post" accept-charset="UTF-8">
                                    ';
                // line 148
                yield craft\helpers\Html::hiddenInput('pluginHandle', $context['handle']);
                yield '
                                    ';
                // line 149
                yield craft\helpers\Html::csrfInput();
                yield '
                                    <button type="button" class="btn menubtn action-btn hairline" aria-label="';
                // line 150
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'html', null, true);
                yield '"></button>
                                    <div class="menu" data-align="right">
                                        <ul>
                                            ';
                // line 153
                if (! craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isInstalled', [], 'any', false, false, false, 153)) {
                    // line 154
                    yield '                                                ';
                    if ((isset($context['forceDisabled']) || array_key_exists('forceDisabled', $context) ? $context['forceDisabled'] : (function () {
                        throw new RuntimeError('Variable "forceDisabled" does not exist.', 154, $this->source);
                    })())) {
                        // line 155
                        yield '                                                    <li><a class="disabled" title="';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('{plugin} can’t be installed due to the {setting} config setting.', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [], 'any', false, false, false, 155), 'setting' => 'disabledPlugins']), 'html', null, true);
                        yield '">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Install', 'app'), 'html', null, true);
                        yield '</a></li>
                                                ';
                    } else {
                        // line 157
                        yield '                                                    <li><a class="formsubmit" data-action="plugins/install-plugin">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Install', 'app'), 'html', null, true);
                        yield '</a></li>
                                                ';
                    }
                    // line 159
                    yield '                                                <li><a class="formsubmit error" data-action="pluginstore/remove" data-param="packageName" data-value="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'packageName', [], 'any', false, false, false, 159), 'html', null, true);
                    yield '">';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Remove', 'app'), 'html', null, true);
                    yield '</a></li>
                                            ';
                } else {
                    // line 161
                    yield '                                                ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isEnabled', [], 'any', false, false, false, 161)) {
                        // line 162
                        yield '                                                    <li><a class="formsubmit" data-action="plugins/disable-plugin">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Disable', 'app'), 'html', null, true);
                        yield '</a></li>
                                                    <li><a class="formsubmit error" data-action="plugins/uninstall-plugin" data-confirm="';
                        // line 163
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Are you sure you want to uninstall {plugin}? You will lose all of its associated data.', 'app', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [], 'any', false, false, false, 163)]), 'html', null, true);
                        yield '">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Uninstall', 'app'), 'html', null, true);
                        yield '</a></li>
                                                ';
                    } elseif (                    // line 164
                        (isset($context['forceDisabled']) || array_key_exists('forceDisabled', $context) ? $context['forceDisabled'] : (function () {
                            throw new RuntimeError('Variable "forceDisabled" does not exist.', 164, $this->source);
                        })())) {
                        // line 165
                        yield '                                                    <li><a class="disabled" title="';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('{plugin} is disabled by the {setting} config setting.', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [], 'any', false, false, false, 165), 'setting' => 'disabledPlugins']), 'html', null, true);
                        yield '">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Enable', 'app'), 'html', null, true);
                        yield '</a></li>
                                                ';
                    } else {
                        // line 167
                        yield '                                                    <li><a class="formsubmit" data-action="plugins/enable-plugin">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Enable', 'app'), 'html', null, true);
                        yield '</a></li>
                                                ';
                    }
                    // line 169
                    yield '                                            ';
                }
                // line 170
                yield '                                        </ul>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['handle'], $context['config'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 176
            yield '                </tbody>
            </table>
        </div>
    ';
        } else {
            // line 180
            yield '        <p id="no-plugins" class="zilch">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('There are no available plugins.', 'app'), 'html', null, true);
            yield '
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
        return 'settings/plugins';
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
        return [488 => 180,  482 => 176,  471 => 170,  468 => 169,  462 => 167,  454 => 165,  452 => 164,  446 => 163,  441 => 162,  438 => 161,  430 => 159,  424 => 157,  416 => 155,  413 => 154,  411 => 153,  405 => 150,  401 => 149,  397 => 148,  392 => 146,  389 => 145,  383 => 143,  377 => 141,  375 => 140,  370 => 139,  368 => 138,  364 => 137,  359 => 134,  352 => 132,  345 => 130,  336 => 128,  329 => 126,  327 => 125,  325 => 124,  318 => 122,  316 => 121,  314 => 119,  305 => 117,  298 => 115,  296 => 114,  294 => 113,  290 => 111,  287 => 110,  283 => 109,  278 => 106,  276 => 105,  275 => 103,  274 => 102,  273 => 98,  264 => 96,  256 => 94,  253 => 93,  249 => 91,  247 => 57,  243 => 90,  238 => 87,  236 => 85,  235 => 84,  232 => 83,  229 => 82,  224 => 79,  222 => 75,  221 => 74,  218 => 73,  215 => 72,  211 => 70,  205 => 68,  201 => 66,  199 => 65,  198 => 62,  196 => 61,  194 => 60,  191 => 59,  188 => 58,  186 => 57,  184 => 56,  181 => 55,  175 => 53,  173 => 52,  167 => 50,  164 => 49,  162 => 44,  161 => 42,  159 => 48,  152 => 47,  145 => 46,  142 => 42,  140 => 41,  136 => 40,  131 => 37,  125 => 35,  123 => 34,  119 => 33,  111 => 32,  100 => 29,  97 => 28,  94 => 27,  90 => 26,  85 => 23,  82 => 22,  74 => 21,  68 => 3,  64 => 185,  62 => 184,  60 => 18,  58 => 15,  57 => 14,  56 => 12,  54 => 7,  52 => 5,  50 => 4,  48 => 1,  40 => 3];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% requireAdmin %}

{% extends \"_layouts/cp\" %}
{% set title = \"Plugins\"|t('app') %}
{% do view.registerAssetBundle(\"craft\\\\web\\\\assets\\\\plugins\\\\PluginsAsset\") %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}


{% set info = craft.app.plugins.getAllPluginInfo()|multisort(
    ['isEnabled', 'isInstalled', 'name'],
    [SORT_DESC, SORT_DESC, SORT_ASC],
    [SORT_NUMERIC, SORT_NUMERIC, SORT_NATURAL]
) %}

{% set disabledPlugins = craft.app.config.general.disabledPlugins %}


{% block content %}
    {% if info|length %}
        <div class=\"tablepane\">
            <table id=\"plugins\" class=\"data fullwidth\">
                <tbody>
                    {% for handle, config in info %}
                        {% set pluginStoreUrl = url('plugin-store/' ~ handle) %}
                        {% set forceDisabled = disabledPlugins == '*' or handle in disabledPlugins %}
                        <tr id=\"plugin-{{ handle }}\" data-name=\"{{ config.name }}\" data-handle=\"{{ handle }}\">
                            <th>
                                <div class=\"plugin-infos\">
                                    <a class=\"icon\" href=\"{{ pluginStoreUrl }}\" title=\"{{ 'View {plugin} in the Plugin Store'|t('app', {plugin: config.name}) }}\" title=\"{{ 'View {plugin} in the Plugin Store'|t('app', {plugin: config.name}) }}\">
                                        {{ svg(craft.app.plugins.getPluginIconSvg(handle), sanitize=true, namespace=true) }}
                                        {% if config.licenseKeyStatus == 'valid' or config.licenseIssues is not empty %}
                                            <span class=\"license-key-status {{ config.licenseIssues is empty ? 'valid' }}\"></span>
                                        {% endif %}
                                    </a>
                                    <div class=\"plugin-details\">
                                        <div class=\"plugin-id\">
                                            <h2>{{ config.name }}</h2>
                                            {% if config.hasMultipleEditions or config.isTrial %}
                                                {% tag (config.upgradeAvailable ? 'a' : 'div') with {
                                                    class: 'edition',
                                                    href: config.upgradeAvailable ? pluginStoreUrl : false,
                                                } %}
                                                    {% if config.hasMultipleEditions %}<div class=\"edition-name\">{{ config.edition }}</div>{% endif %}
                                                    {% if config.isTrial %}<div class=\"edition-trial\">{{ 'Trial'|t('app') }}</div>{% endif %}
                                                {% endtag %}
                                            {% endif %}
                                            <span class=\"version\">{{ config.version }}</span>
                                        </div>
                                        {% if config.description %}
                                            <p>{{ config.description }}</p>
                                        {% endif %}
                                        {% if config.developer or config.documentationUrl or config.hasCpSettings %}
                                            <ul class=\"links\">
                                                {%- apply spaceless %}
                                                    {% if config.developer %}
                                                        <li class=\"link-developer\">
                                                            {% if config.developerUrl %}
                                                                {{ tag('a', {
                                                                    href: config.developerUrl,
                                                                    rel: 'noopener',
                                                                    target: '_blank',
                                                                    text: config.developer,
                                                                }) }}
                                                            {% else %}
                                                                <span>{{ config.developer }}</span>
                                                            {% endif %}
                                                        </li>
                                                    {% endif %}
                                                    {% if config.documentationUrl %}
                                                        <li class=\"link-docs\">
                                                            {{ tag('a', {
                                                                href: config.documentationUrl,
                                                                rel: 'noopener',
                                                                target: '_blank',
                                                                text: 'Documentation'|t('app'),
                                                            }) }}
                                                        </li>
                                                    {% endif %}
                                                    {% if config.hasCpSettings %}
                                                        <li class=\"link-settings\">
                                                            {{ tag('a', {
                                                                href: url('settings/plugins/'~config.moduleId),
                                                                text: 'Settings'|t('app'),
                                                            }) }}
                                                        </li>
                                                    {% endif %}
                                                {% endapply -%}
                                            </ul>
                                        {% endif %}
                                        {% set showLicenseKey = config.licenseKey or config.licenseKeyStatus != 'unknown' %}
                                        <div class=\"flex license-key{% if not showLicenseKey %} hidden{% endif %}\">
                                            <div class=\"pane\">
                                                <input class=\"text code{% if config.licenseIssues is not empty %} error{% endif %}\" size=\"29\" value=\"{{ config.licenseKey[0:1] == '\$' ? config.licenseKey : (config.licenseKey|replace('/.{4}/', '\$0-')|trim('-')) }}\" placeholder=\"XXXX-XXXX-XXXX-XXXX-XXXX-XXXX\" readonly>
                                            </div>
                                            {{ tag('a', {
                                                text: 'Buy now'|t('app'),
                                                class: [
                                                    'btn',
                                                    config.licenseIssues is not empty ? 'submit',
                                                    config.licenseKeyStatus != 'trial' ? 'hidden',
                                                ]|filter,
                                                href: url('plugin-store/buy/'~handle~'/'~config.edition),
                                            }) }}
                                            <div class=\"spinner hidden\"></div>
                                        </div>
                                        {% for issue in config.licenseIssues %}
                                            <p class=\"error\">
                                                {% switch issue %}
                                                    {% case 'wrong_edition' %}
                                                        {{ 'This license is for the {name} edition.'|t('app', {
                                                            name: config.licensedEdition|ucfirst
                                                        }) }}
                                                    {% case 'no_trials' %}
                                                        {{ 'Plugin trials are not allowed on this domain.'|t('app') }}
                                                    {% case 'mismatched' %}
                                                        {{ 'This license is tied to another Craft install. Visit {accountLink} to detach it, or <a href=\"{buyUrl}\">buy a new license</a>.'|t('app', {
                                                            accountLink: '<a href=\"https://console.craftcms.com\" rel=\"noopener\" target=\"_blank\">console.craftcms.com</a>',
                                                            buyUrl: url('plugin-store/buy/'~handle~'/'~config.edition),
                                                        })|raw }}
                                                    {% case 'astray' %}
                                                        {{ 'This license isn’t allowed to run version {version}.'|t('app', {
                                                            version: config.version
                                                        }) }}
                                                    {% case 'required' %}
                                                        {{ 'A license key is required.'|t('app') }}
                                                    {% default %}
                                                        {{ 'Your license key is invalid.'|t('app') }}
                                                {% endswitch %}
                                            </p>
                                        {% endfor %}
                                    </div>
                                </div>
                            </th>
                            <td class=\"nowrap\" data-title=\"{{ 'Status'|t('app') }}\">
                                {% if config.isEnabled %}
                                    <span class=\"status on\"></span>{{ \"Installed\"|t('app') }}
                                {% elseif config.isInstalled %}
                                    <span class=\"status off\"></span>{{ \"Disabled\"|t('app') }}
                                {% else %}
                                    <span class=\"status\"></span><span class=\"light\">{{ \"Not installed\"|t('app') }}</span>
                                {% endif %}
                            </td>
                            <td class=\"nowrap thin\" data-title=\"{{ 'Action'|t('app') }}\">
                                <form method=\"post\" accept-charset=\"UTF-8\">
                                    {{ hiddenInput('pluginHandle', handle) }}
                                    {{ csrfInput() }}
                                    <button type=\"button\" class=\"btn menubtn action-btn hairline\" aria-label=\"{{ 'Settings'|t('app') }}\"></button>
                                    <div class=\"menu\" data-align=\"right\">
                                        <ul>
                                            {% if not config.isInstalled %}
                                                {% if forceDisabled %}
                                                    <li><a class=\"disabled\" title=\"{{ '{plugin} can’t be installed due to the {setting} config setting.'|t({plugin: config.name, setting: 'disabledPlugins'}) }}\">{{ 'Install'|t('app') }}</a></li>
                                                {% else %}
                                                    <li><a class=\"formsubmit\" data-action=\"plugins/install-plugin\">{{ 'Install'|t('app') }}</a></li>
                                                {% endif %}
                                                <li><a class=\"formsubmit error\" data-action=\"pluginstore/remove\" data-param=\"packageName\" data-value=\"{{ config.packageName }}\">{{ 'Remove'|t('app') }}</a></li>
                                            {% else %}
                                                {% if config.isEnabled %}
                                                    <li><a class=\"formsubmit\" data-action=\"plugins/disable-plugin\">{{ 'Disable'|t('app') }}</a></li>
                                                    <li><a class=\"formsubmit error\" data-action=\"plugins/uninstall-plugin\" data-confirm=\"{{ 'Are you sure you want to uninstall {plugin}? You will lose all of its associated data.'|t('app', { plugin: config.name }) }}\">{{ 'Uninstall'|t('app') }}</a></li>
                                                {% elseif forceDisabled %}
                                                    <li><a class=\"disabled\" title=\"{{ '{plugin} is disabled by the {setting} config setting.'|t({plugin: config.name, setting: 'disabledPlugins'}) }}\">{{ 'Enable'|t('app') }}</a></li>
                                                {% else %}
                                                    <li><a class=\"formsubmit\" data-action=\"plugins/enable-plugin\">{{ 'Enable'|t('app') }}</a></li>
                                                {% endif %}
                                            {% endif %}
                                        </ul>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    {% else %}
        <p id=\"no-plugins\" class=\"zilch\">{{ \"There are no available plugins.\"|t('app') }}
    {% endif %}
{% endblock %}

{% js %}
new Craft.PluginManager();
{% endjs %}
", 'settings/plugins', '/tmp/packages/craft5/src/templates/settings/plugins/index.twig');
    }
}
