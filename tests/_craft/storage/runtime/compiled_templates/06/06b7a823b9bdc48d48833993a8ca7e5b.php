<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* settings/plugins */
class __TwigTemplate_30b67e650d8f6d2a337de805d22bd57e extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 3
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'settings/plugins');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Plugins', 'app');
        // line 5
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 5, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\plugins\\PluginsAsset'], 'method');
        // line 7
        $context['crumbs'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 12
        $context['info'] = $this->extensions['craft\web\twig\Extension']->multisortFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 12, $this->source);
        })()), 'app', []), 'plugins', []), 'getAllPluginInfo', [], 'method'), [0 => 'isEnabled', 1 => 'isInstalled', 2 => 'name'], [0 =>         // line 14
(isset($context['SORT_DESC']) || array_key_exists('SORT_DESC', $context) ? $context['SORT_DESC'] : (function () {
    throw new RuntimeError('Variable "SORT_DESC" does not exist.', 14, $this->source);
})()), 1 => (isset($context['SORT_DESC']) || array_key_exists('SORT_DESC', $context) ? $context['SORT_DESC'] : (function () {
    throw new RuntimeError('Variable "SORT_DESC" does not exist.', 14, $this->source);
})()), 2 => (isset($context['SORT_ASC']) || array_key_exists('SORT_ASC', $context) ? $context['SORT_ASC'] : (function () {
    throw new RuntimeError('Variable "SORT_ASC" does not exist.', 14, $this->source);
})()), ], [0 =>         // line 15
(isset($context['SORT_NUMERIC']) || array_key_exists('SORT_NUMERIC', $context) ? $context['SORT_NUMERIC'] : (function () {
    throw new RuntimeError('Variable "SORT_NUMERIC" does not exist.', 15, $this->source);
})()), 1 => (isset($context['SORT_NUMERIC']) || array_key_exists('SORT_NUMERIC', $context) ? $context['SORT_NUMERIC'] : (function () {
    throw new RuntimeError('Variable "SORT_NUMERIC" does not exist.', 15, $this->source);
})()), 2 => (isset($context['SORT_NATURAL']) || array_key_exists('SORT_NATURAL', $context) ? $context['SORT_NATURAL'] : (function () {
    throw new RuntimeError('Variable "SORT_NATURAL" does not exist.', 15, $this->source);
})()), ]);
        // line 18
        $context['disabledPlugins'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 18, $this->source);
        })()), 'app', []), 'config', []), 'general', []), 'disabledPlugins', []);
        // line 186
        ob_start();
        // line 187
        echo 'new Craft.PluginManager();
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/plugins', 3);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/plugins');
    }

    // line 21
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 22
        echo '    ';
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['info']) || array_key_exists('info', $context) ? $context['info'] : (function () {
            throw new RuntimeError('Variable "info" does not exist.', 22, $this->source);
        })()))) {
            // line 23
            echo '        <div class="tablepane">
            <table id="plugins" class="data fullwidth">
                <tbody>
                    ';
            // line 26
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['info']) || array_key_exists('info', $context) ? $context['info'] : (function () {
                throw new RuntimeError('Variable "info" does not exist.', 26, $this->source);
            })()));
            foreach ($context['_seq'] as $context['handle'] => $context['config']) {
                // line 27
                echo '                        ';
                $context['pluginStoreUrl'] = craft\helpers\UrlHelper::url(('plugin-store/'.$context['handle']));
                // line 28
                echo '                        ';
                $context['forceDisabled'] = (((isset($context['disabledPlugins']) || array_key_exists('disabledPlugins', $context) ? $context['disabledPlugins'] : (function () {
                    throw new RuntimeError('Variable "disabledPlugins" does not exist.', 28, $this->source);
                })()) == '*') || twig_in_filter($context['handle'], (isset($context['disabledPlugins']) || array_key_exists('disabledPlugins', $context) ? $context['disabledPlugins'] : (function () {
                    throw new RuntimeError('Variable "disabledPlugins" does not exist.', 28, $this->source);
                })())));
                // line 29
                echo '                        <tr id="plugin-';
                echo twig_escape_filter($this->env, $context['handle'], 'html', null, true);
                echo '" data-name="';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', []), 'html', null, true);
                echo '" data-handle="';
                echo twig_escape_filter($this->env, $context['handle'], 'html', null, true);
                echo '">
                            <th>
                                <div class="plugin-infos">
                                    <a class="icon" href="';
                // line 32
                echo twig_escape_filter($this->env, (isset($context['pluginStoreUrl']) || array_key_exists('pluginStoreUrl', $context) ? $context['pluginStoreUrl'] : (function () {
                    throw new RuntimeError('Variable "pluginStoreUrl" does not exist.', 32, $this->source);
                })()), 'html', null, true);
                echo '" title="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('View {plugin} in the Plugin Store', 'app', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [])]), 'html', null, true);
                echo '" title="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('View {plugin} in the Plugin Store', 'app', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [])]), 'html', null, true);
                echo '">
                                        ';
                // line 33
                echo $this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 33, $this->source);
                })()), 'app', []), 'plugins', []), 'getPluginIconSvg', [0 => $context['handle']], 'method'), true, true);
                echo '
                                        ';
                // line 34
                if (((craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKeyStatus', []) == 'valid') || ! twig_test_empty(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseIssues', [])))) {
                    // line 35
                    echo '                                            <span class="license-key-status ';
                    echo (twig_test_empty(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseIssues', []))) ? ('valid') : ('');
                    echo '"></span>
                                        ';
                }
                // line 37
                echo '                                    </a>
                                    <div class="plugin-details">
                                        <div class="plugin-id">
                                            <h2>';
                // line 40
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', []), 'html', null, true);
                echo '</h2>
                                            ';
                // line 41
                if ((craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'hasMultipleEditions', []) || craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isTrial', []))) {
                    // line 42
                    echo '                                                ';
                    ob_start();
                    // line 46
                    echo '                                                    ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'hasMultipleEditions', [])) {
                        echo '<div class="edition-name">';
                        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'edition', []), 'html', null, true);
                        echo '</div>';
                    }
                    // line 47
                    echo '                                                    ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isTrial', [])) {
                        echo '<div class="edition-trial">';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Trial', 'app'), 'html', null, true);
                        echo '</div>';
                    }
                    // line 48
                    echo '                                                ';
                    echo craft\helpers\Html::tag(((craft\helpers\Template::attribute($this->env, $this->source,                     // line 42
                        $context['config'], 'upgradeAvailable', [])) ? ('a') : ('div')), ob_get_clean(), ['class' => 'edition', 'href' => ((craft\helpers\Template::attribute($this->env, $this->source,                     // line 44
                            $context['config'], 'upgradeAvailable', [])) ? ((isset($context['pluginStoreUrl']) || array_key_exists('pluginStoreUrl', $context) ? $context['pluginStoreUrl'] : (function () {
                                throw new RuntimeError('Variable "pluginStoreUrl" does not exist.', 44, $this->source);
                            })())) : (false))]);
                    // line 49
                    echo '                                            ';
                }
                // line 50
                echo '                                            <span class="version">';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'version', []), 'html', null, true);
                echo '</span>
                                        </div>
                                        ';
                // line 52
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'description', [])) {
                    // line 53
                    echo '                                            <p>';
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'description', []), 'html', null, true);
                    echo '</p>
                                        ';
                }
                // line 55
                echo '                                        ';
                if (((craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'developer', []) || craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'documentationUrl', [])) || craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'hasCpSettings', []))) {
                    // line 56
                    echo '                                            <ul class="links">';
                    // line 57
                    ob_start();
                    // line 58
                    echo '                                                    ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'developer', [])) {
                        // line 59
                        echo '                                                        <li class="link-developer">
                                                            ';
                        // line 60
                        if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'developerUrl', [])) {
                            // line 61
                            echo '                                                                ';
                            echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\Template::attribute($this->env, $this->source,                             // line 62
                                $context['config'], 'developerUrl', []), 'rel' => 'noopener', 'target' => '_blank', 'text' => craft\helpers\Template::attribute($this->env, $this->source,                             // line 65
                                    $context['config'], 'developer', [])]);
                            // line 66
                            echo '
                                                            ';
                        } else {
                            // line 68
                            echo '                                                                <span>';
                            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'developer', []), 'html', null, true);
                            echo '</span>
                                                            ';
                        }
                        // line 70
                        echo '                                                        </li>
                                                    ';
                    }
                    // line 72
                    echo '                                                    ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'documentationUrl', [])) {
                        // line 73
                        echo '                                                        <li class="link-docs">
                                                            ';
                        // line 74
                        echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\Template::attribute($this->env, $this->source,                         // line 75
                            $context['config'], 'documentationUrl', []), 'rel' => 'noopener', 'target' => '_blank', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Documentation', 'app')]);
                        // line 79
                        echo '
                                                        </li>
                                                    ';
                    }
                    // line 82
                    echo '                                                    ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'hasCpSettings', [])) {
                        // line 83
                        echo '                                                        <li class="link-settings">
                                                            ';
                        // line 84
                        echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => craft\helpers\UrlHelper::url(('settings/plugins/'.craft\helpers\Template::attribute($this->env, $this->source,                         // line 85
                            $context['config'], 'moduleId', []))), 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app')]);
                        // line 87
                        echo '
                                                        </li>
                                                    ';
                    }
                    // line 90
                    echo '                                                ';
                    $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
                    // line 57
                    echo twig_spaceless($___internal_parse_0_);
                    // line 91
                    echo '</ul>
                                        ';
                }
                // line 93
                echo '                                        ';
                $context['showLicenseKey'] = (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKey', []) || (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKeyStatus', []) != 'unknown'));
                // line 94
                echo '                                        <div class="flex license-key';
                if (! (isset($context['showLicenseKey']) || array_key_exists('showLicenseKey', $context) ? $context['showLicenseKey'] : (function () {
                    throw new RuntimeError('Variable "showLicenseKey" does not exist.', 94, $this->source);
                })())) {
                    echo ' hidden';
                }
                echo '">
                                            <div class="pane">
                                                <input class="text code';
                // line 96
                if (! twig_test_empty(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseIssues', []))) {
                    echo ' error';
                }
                echo '" size="29" value="';
                echo twig_escape_filter($this->env, (((twig_slice($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKey', []), 0, 1) == '$')) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKey', [])) : (twig_trim_filter($this->extensions['craft\web\twig\Extension']->replaceFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseKey', []), '/.{4}/', '$0-'), '-'))), 'html', null, true);
                echo '" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX-XXXX" readonly>
                                            </div>
                                            ';
                // line 98
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Buy now', 'app'), 'class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'btn', 1 => ((! twig_test_empty(craft\helpers\Template::attribute($this->env, $this->source,                 // line 102
                    $context['config'], 'licenseIssues', []))) ? ('submit') : ('')), 2 => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 103
                        $context['config'], 'licenseKeyStatus', []) != 'trial')) ? ('hidden') : (''))]), 'href' => craft\helpers\UrlHelper::url(((('plugin-store/buy/'.                 // line 105
                        $context['handle']).'/').craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'edition', [])))]);
                // line 106
                echo '
                                            <div class="spinner hidden"></div>
                                        </div>
                                        ';
                // line 109
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'licenseIssues', []));
                foreach ($context['_seq'] as $context['_key'] => $context['issue']) {
                    // line 110
                    echo '                                            <p class="error">
                                                ';
                    // line 111
                    switch ($context['issue']) {
                        case 'wrong_edition':
                            // line 113
                            echo '                                                        ';
                            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('This license is for the {name} edition.', 'app', ['name' => $this->extensions['craft\web\twig\Extension']->ucfirstFilter(craft\helpers\Template::attribute($this->env, $this->source,                             // line 114
                                $context['config'], 'licensedEdition', []))]), 'html', null, true);
                            // line 115
                            echo '
                                                    ';
                            break;
                        case 'no_trials':
                            // line 117
                            echo '                                                        ';
                            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Plugin trials are not allowed on this domain.', 'app'), 'html', null, true);
                            echo '
                                                    ';
                            break;
                        case 'mismatched':
                            // line 119
                            echo '                                                        ';
                            echo $this->extensions['craft\web\twig\Extension']->translateFilter('This license is tied to another Craft install. Visit {accountLink} to detach it, or <a href="{buyUrl}">buy a new license</a>.', 'app', ['accountLink' => '<a href="https://console.craftcms.com" rel="noopener" target="_blank">console.craftcms.com</a>', 'buyUrl' => craft\helpers\UrlHelper::url(((('plugin-store/buy/'.                             // line 121
$context['handle']).'/').craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'edition', [])))]);
                            // line 122
                            echo '
                                                    ';
                            break;
                        case 'astray':
                            // line 124
                            echo '                                                        ';
                            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('This license isn’t allowed to run version {version}.', 'app', ['version' => craft\helpers\Template::attribute($this->env, $this->source,                             // line 125
                                $context['config'], 'version', [])]), 'html', null, true);
                            // line 126
                            echo '
                                                    ';
                            break;
                        case 'required':
                            // line 128
                            echo '                                                        ';
                            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('A license key is required.', 'app'), 'html', null, true);
                            echo '
                                                    ';
                            break;
                        default:
                            // line 130
                            echo '                                                        ';
                            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Your license key is invalid.', 'app'), 'html', null, true);
                            echo '
                                                ';
                    }
                    // line 132
                    echo '                                            </p>
                                        ';
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['issue'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 134
                echo '                                    </div>
                                </div>
                            </th>
                            <td class="nowrap" data-title="';
                // line 137
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Status', 'app'), 'html', null, true);
                echo '">
                                ';
                // line 138
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isEnabled', [])) {
                    // line 139
                    echo '                                    <span class="status on"></span>';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Installed', 'app'), 'html', null, true);
                    echo '
                                ';
                } elseif (craft\helpers\Template::attribute($this->env, $this->source,                 // line 140
                    $context['config'], 'isInstalled', [])) {
                    // line 141
                    echo '                                    <span class="status off"></span>';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Disabled', 'app'), 'html', null, true);
                    echo '
                                ';
                } else {
                    // line 143
                    echo '                                    <span class="status"></span><span class="light">';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Not installed', 'app'), 'html', null, true);
                    echo '</span>
                                ';
                }
                // line 145
                echo '                            </td>
                            <td class="nowrap thin" data-title="';
                // line 146
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Action', 'app'), 'html', null, true);
                echo '">
                                <form method="post" accept-charset="UTF-8">
                                    ';
                // line 148
                echo craft\helpers\Html::hiddenInput('pluginHandle', $context['handle']);
                echo '
                                    ';
                // line 149
                echo craft\helpers\Html::csrfInput();
                echo '
                                    <div class="btngroup">
                                        <button type="button" class="btn menubtn" data-icon="settings" aria-label="';
                // line 151
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'html', null, true);
                echo '"></button>
                                        <div class="menu" data-align="right">
                                            <ul>
                                                ';
                // line 154
                if (! craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isInstalled', [])) {
                    // line 155
                    echo '                                                    ';
                    if ((isset($context['forceDisabled']) || array_key_exists('forceDisabled', $context) ? $context['forceDisabled'] : (function () {
                        throw new RuntimeError('Variable "forceDisabled" does not exist.', 155, $this->source);
                    })())) {
                        // line 156
                        echo '                                                        <li><a class="disabled" title="';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('{plugin} can’t be installed due to the {setting} config setting.', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', []), 'setting' => 'disabledPlugins']), 'html', null, true);
                        echo '">';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Install', 'app'), 'html', null, true);
                        echo '</a></li>
                                                    ';
                    } else {
                        // line 158
                        echo '                                                        <li><a class="formsubmit" data-action="plugins/install-plugin">';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Install', 'app'), 'html', null, true);
                        echo '</a></li>
                                                    ';
                    }
                    // line 160
                    echo '                                                    <li><a class="formsubmit error" data-action="pluginstore/remove" data-param="packageName" data-value="';
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'packageName', []), 'html', null, true);
                    echo '">';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Remove', 'app'), 'html', null, true);
                    echo '</a></li>
                                                ';
                } else {
                    // line 162
                    echo '                                                    ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'isEnabled', [])) {
                        // line 163
                        echo '                                                        <li><a class="formsubmit" data-action="plugins/disable-plugin">';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Disable', 'app'), 'html', null, true);
                        echo '</a></li>
                                                        <li><a class="formsubmit error" data-action="plugins/uninstall-plugin" data-confirm="';
                        // line 164
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Are you sure you want to uninstall {plugin}? You will lose all of its associated data.', 'app', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', [])]), 'html', null, true);
                        echo '">';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Uninstall', 'app'), 'html', null, true);
                        echo '</a></li>
                                                    ';
                    } elseif (                    // line 165
                        (isset($context['forceDisabled']) || array_key_exists('forceDisabled', $context) ? $context['forceDisabled'] : (function () {
                            throw new RuntimeError('Variable "forceDisabled" does not exist.', 165, $this->source);
                        })())) {
                        // line 166
                        echo '                                                        <li><a class="disabled" title="';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('{plugin} is disabled by the {setting} config setting.', ['plugin' => craft\helpers\Template::attribute($this->env, $this->source, $context['config'], 'name', []), 'setting' => 'disabledPlugins']), 'html', null, true);
                        echo '">';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Enable', 'app'), 'html', null, true);
                        echo '</a></li>
                                                    ';
                    } else {
                        // line 168
                        echo '                                                        <li><a class="formsubmit" data-action="plugins/enable-plugin">';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Enable', 'app'), 'html', null, true);
                        echo '</a></li>
                                                    ';
                    }
                    // line 170
                    echo '                                                ';
                }
                // line 171
                echo '                                            </ul>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['handle'], $context['config'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 178
            echo '                </tbody>
            </table>
        </div>
    ';
        } else {
            // line 182
            echo '        <p id="no-plugins" class="zilch">';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('There are no available plugins.', 'app'), 'html', null, true);
            echo '
    ';
        }
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/plugins';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [481 => 182,  475 => 178,  463 => 171,  460 => 170,  454 => 168,  446 => 166,  444 => 165,  438 => 164,  433 => 163,  430 => 162,  422 => 160,  416 => 158,  408 => 156,  405 => 155,  403 => 154,  397 => 151,  392 => 149,  388 => 148,  383 => 146,  380 => 145,  374 => 143,  368 => 141,  366 => 140,  361 => 139,  359 => 138,  355 => 137,  350 => 134,  343 => 132,  336 => 130,  327 => 128,  320 => 126,  318 => 125,  316 => 124,  309 => 122,  307 => 121,  305 => 119,  296 => 117,  289 => 115,  287 => 114,  285 => 113,  281 => 111,  278 => 110,  274 => 109,  269 => 106,  267 => 105,  266 => 103,  265 => 102,  264 => 98,  255 => 96,  247 => 94,  244 => 93,  240 => 91,  238 => 57,  235 => 90,  230 => 87,  228 => 85,  227 => 84,  224 => 83,  221 => 82,  216 => 79,  214 => 75,  213 => 74,  210 => 73,  207 => 72,  203 => 70,  197 => 68,  193 => 66,  191 => 65,  190 => 62,  188 => 61,  186 => 60,  183 => 59,  180 => 58,  178 => 57,  176 => 56,  173 => 55,  167 => 53,  165 => 52,  159 => 50,  156 => 49,  154 => 44,  153 => 42,  151 => 48,  144 => 47,  137 => 46,  134 => 42,  132 => 41,  128 => 40,  123 => 37,  117 => 35,  115 => 34,  111 => 33,  103 => 32,  92 => 29,  89 => 28,  86 => 27,  82 => 26,  77 => 23,  74 => 22,  69 => 21,  63 => 3,  59 => 187,  57 => 186,  55 => 18,  53 => 15,  52 => 14,  51 => 12,  49 => 7,  47 => 5,  45 => 4,  43 => 1,  35 => 3];
    }

    public function getSourceContext()
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
                                    <div class=\"btngroup\">
                                        <button type=\"button\" class=\"btn menubtn\" data-icon=\"settings\" aria-label=\"{{ 'Settings'|t('app') }}\"></button>
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
", 'settings/plugins', '/Users/brianhanson/Development/craft5/src/templates/settings/plugins/index.twig');
    }
}
