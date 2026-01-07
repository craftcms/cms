<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/cp */
class __TwigTemplate_a53ee7ab9e0e36ba8e111a2a8e6858bc extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'body' => $this->block_body(...),
            'mainFormAttributes' => $this->block_mainFormAttributes(...),
            'header' => $this->block_header(...),
            'pageTitle' => $this->block_pageTitle(...),
            'main' => $this->block_main(...),
            'content' => $this->block_content(...),
            'actionButton' => $this->block_actionButton(...),
            'submitButton' => $this->block_submitButton(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 42
        return '_layouts/basecp.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_layouts/cp');
        // line 45
        $context['queue'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 45, $this->source);
        })()), 'app', []), 'queue', []);
        // line 46
        ob_start();
        // line 47
        if ($this->env->getTest('instance of')->getCallable()((isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
            throw new RuntimeError('Variable "queue" does not exist.', 47, $this->source);
        })()), 'craft\\queue\\QueueInterface')) {
            // line 48
            echo '    Craft.cp.setJobInfo(';
            echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
                throw new RuntimeError('Variable "queue" does not exist.', 48, $this->source);
            })()), 'getJobInfo', [0 => 100], 'method'));
            echo ', false);
    ';
            // line 49
            if (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
                throw new RuntimeError('Variable "queue" does not exist.', 49, $this->source);
            })()), 'getHasReservedJobs', [], 'method')) {
                // line 50
                echo '        Craft.cp.trackJobProgress(true);
    ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 51
                (isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
                    throw new RuntimeError('Variable "queue" does not exist.', 51, $this->source);
                })()), 'getHasWaitingJobs', [], 'method')) {
                // line 52
                echo '        Craft.cp.runQueue();
    ';
            }
        } else {
            // line 55
            echo '    Craft.cp.enableQueue = false;
';
        }
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 59
        $context['hasSystemIcon'] = (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 59, $this->source);
        })()) == (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 59, $this->source);
        })())) && craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 59, $this->source);
        })()), 'rebrand', []), 'isIconUploaded', []));
        // line 60
        $context['fullPageForm'] = (array_key_exists('fullPageForm', $context) && (isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 60, $this->source);
        })()));
        // line 62
        $context['editionName'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 62, $this->source);
        })()), 'app', []), 'getEditionName', [], 'method');
        // line 63
        $context['canUpgradeEdition'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 63, $this->source);
        })()), 'app', []), 'getCanUpgradeEdition', [], 'method');
        // line 64
        $context['licensedEdition'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 64, $this->source);
        })()), 'app', []), 'getLicensedEdition', [], 'method');
        // line 65
        $context['isTrial'] = (! ((isset($context['licensedEdition']) || array_key_exists('licensedEdition', $context) ? $context['licensedEdition'] : (function () {
            throw new RuntimeError('Variable "licensedEdition" does not exist.', 65, $this->source);
        })()) === null) && ! ((isset($context['licensedEdition']) || array_key_exists('licensedEdition', $context) ? $context['licensedEdition'] : (function () {
            throw new RuntimeError('Variable "licensedEdition" does not exist.', 65, $this->source);
        })()) === (isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 65, $this->source);
        })())));
        // line 66
        $context['trialInfo'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 66, $this->source);
        })()), 'cp', []), 'trialInfo', [], 'method');
        // line 68
        $context['contentNotice'] = twig_trim_filter((($context['contentNotice']) ?? ((($this->hasBlock('contentNotice', $context, $blocks)) ? ($this->renderBlock('contentNotice', $context, $blocks)) : ('')))));
        // line 69
        $context['sidebar'] = twig_trim_filter((($context['sidebar']) ?? ((($this->hasBlock('sidebar', $context, $blocks)) ? ($this->renderBlock('sidebar', $context, $blocks)) : ('')))));
        // line 70
        $context['toolbar'] = twig_trim_filter((($context['toolbar']) ?? ((($this->hasBlock('toolbar', $context, $blocks)) ? ($this->renderBlock('toolbar', $context, $blocks)) : ('')))));
        // line 71
        $context['actionButton'] = twig_trim_filter((($this->hasBlock('actionButton', $context, $blocks)) ? ($this->renderBlock('actionButton', $context, $blocks)) : ('')));
        // line 72
        $context['additionalButtons'] ??= null;
        // line 73
        $context['details'] = twig_trim_filter((($context['details']) ?? ((($this->hasBlock('details', $context, $blocks)) ? ($this->renderBlock('details', $context, $blocks)) : ('')))));
        // line 74
        $context['footer'] = twig_trim_filter((($context['footer']) ?? ((($this->hasBlock('footer', $context, $blocks)) ? ($this->renderBlock('footer', $context, $blocks)) : ('')))));
        // line 75
        $context['crumbs'] ??= null;
        // line 76
        $context['contextMenu'] = twig_trim_filter((($context['contextMenu']) ?? ((($this->hasBlock('contextMenu', $context, $blocks)) ? ($this->renderBlock('contextMenu', $context, $blocks)) : ('')))));
        // line 77
        $context['actionMenu'] ??= '';
        // line 78
        $context['tabs'] = ((($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (($context['tabs']) ?? ([]))) > 1)) ? ((isset($context['tabs']) || array_key_exists('tabs', $context) ? $context['tabs'] : (function () {
            throw new RuntimeError('Variable "tabs" does not exist.', 78, $this->source);
        })())) : (null));
        // line 79
        $context['errorSummary'] ??= null;
        // line 81
        $context['mainContentClasses'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => ((        // line 82
            (isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
                throw new RuntimeError('Variable "sidebar" does not exist.', 82, $this->source);
            })())) ? ('has-sidebar') : ('')), 1 => ((        // line 83
                (isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
                    throw new RuntimeError('Variable "details" does not exist.', 83, $this->source);
                })())) ? ('has-details') : (''))]);
        // line 86
        $context['bodyClass'] = craft\helpers\Html::explodeClass((($context['bodyClass']) ?? ([])));
        // line 87
        $context['showHeader'] ??= true;
        // line 88
        if (! (isset($context['showHeader']) || array_key_exists('showHeader', $context) ? $context['showHeader'] : (function () {
            throw new RuntimeError('Variable "showHeader" does not exist.', 88, $this->source);
        })())) {
            // line 89
            $context['bodyClass'] = $this->extensions['craft\web\twig\Extension']->pushFilter((isset($context['bodyClass']) || array_key_exists('bodyClass', $context) ? $context['bodyClass'] : (function () {
                throw new RuntimeError('Variable "bodyClass" does not exist.', 89, $this->source);
            })()), 'no-header');
        }
        // line 91
        if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 91, $this->source);
        })()), 'app', []), 'hasModule', [0 => 'debug'], 'method')) {
            // line 92
            $context['bodyClass'] = $this->extensions['craft\web\twig\Extension']->pushFilter((isset($context['bodyClass']) || array_key_exists('bodyClass', $context) ? $context['bodyClass'] : (function () {
                throw new RuntimeError('Variable "bodyClass" does not exist.', 92, $this->source);
            })()), 'has-debug-toolbar');
        }
        // line 95
        $context['mainAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' => 'main', 'role' => 'main'], ((        // line 98
            $context['mainAttributes']) ?? ([])));
        // line 100
        $context['formActions'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 100, $this->source);
        })()), 'cp', []), 'prepFormActions', [0 => (($context['formActions']) ?? (null))], 'method');
        // line 102
        $context['mainFormAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' => 'main-form', 'method' => 'post', 'accept-charset' => 'UTF-8', 'novalidate' => true, 'data' => ['saveshortcut' => ((        // line 108
            $context['saveShortcut']) ?? (true)), 'saveshortcut-redirect' => ((((        // line 109
                $context['saveShortcutRedirect']) ?? (false))) ? ($this->env->getFilter('hash')->getCallable()((isset($context['saveShortcutRedirect']) || array_key_exists('saveShortcutRedirect', $context) ? $context['saveShortcutRedirect'] : (function () {
                    throw new RuntimeError('Variable "saveShortcutRedirect" does not exist.', 109, $this->source);
                })()))) : (false)), 'saveshortcut-scroll' => ((        // line 110
                    $context['retainScrollOnSaveShortcut']) ?? (false)), 'actions' => ((        // line 111
                        $context['formActions']) ?? (false)), 'confirm-unload' => true, 'delta' => craft\helpers\Template::attribute($this->env, $this->source,         // line 113
                            (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                                throw new RuntimeError('Variable "view" does not exist.', 113, $this->source);
                            })()), 'getIsDeltaRegistrationActive', [], 'method'), 'delta-names' => craft\helpers\Template::attribute($this->env, $this->source,         // line 114
                                (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                                    throw new RuntimeError('Variable "view" does not exist.', 114, $this->source);
                                })()), 'getDeltaNames', [], 'method'), 'initial-delta-values' => craft\helpers\Template::attribute($this->env, $this->source,         // line 115
                                    (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                                        throw new RuntimeError('Variable "view" does not exist.', 115, $this->source);
                                    })()), 'getInitialDeltaValues', [], 'method'), 'modified-delta-names' => (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 116
                                        ($context['craft'] ?? null), 'app', [], 'any', false, true), 'request', [], 'any', false, true), 'getBodyParam', [0 => 'modifiedDeltaNames'], 'method', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true), 'request', [], 'any', false, true), 'getBodyParam', [0 => 'modifiedDeltaNames'], 'method') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true), 'request', [], 'any', false, true), 'getBodyParam', [0 => 'modifiedDeltaNames'], 'method')) : ([]))]], ((        // line 118
                                            $context['mainFormAttributes']) ?? ([])), true);
        // line 120
        $context['userPhoto'] = twig_include($this->env, $context, '_layouts/components/header-photo.twig');
        // line 122
        ob_start();
        // line 123
        echo "// Remove the hash so the browser doesn't scroll to it
window.LOCATION_HASH = document.location.hash ? decodeURIComponent(document.location.hash.substr(1)) : null;
history.replaceState(undefined, undefined, window.location.href.match(/^[^#]*/)[0]);
";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 1]);
        // line 399
        if ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 399, $this->source);
        })()), 'can', [0 => 'performUpdates'], 'method') && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 399, $this->source);
        })()), 'app', []), 'updates', []), 'getIsUpdateInfoCached', [], 'method'))) {
            // line 400
            ob_start();
            // line 401
            echo '    Craft.cp.checkForUpdates();
    ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        // line 42
        $this->parent = $this->loadTemplate('_layouts/basecp.twig', '_layouts/cp', 42);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/cp');
    }

    // line 128
    public function block_body($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'body');
        // line 129
        echo '    ';
        echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['id' => 'global-skip-link', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Skip to content', 'app'), 'href' => '#main', 'class' => 'skip-link btn']);
        // line 134
        echo '

    <div id="global-container">
        ';
        // line 137
        $this->loadTemplate('_layouts/components/global-sidebar', '_layouts/cp', 137)->display($context);
        // line 138
        echo '
        <div id="page-container">
            ';
        // line 140
        $this->loadTemplate('_layouts/components/alerts', '_layouts/cp', 140)->display($context);
        // line 141
        echo '
            <div id="global-header" role="region" aria-label="';
        // line 142
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('My Account', 'app'), 'html', null, true);
        echo '">

                <div class="flex flex-nowrap gap-xs">
                    ';
        // line 145
        $this->loadTemplate('_layouts/components/crumbs', '_layouts/cp', 145)->display($context);
        // line 146
        echo '                    ';
        if ((isset($context['contextMenu']) || array_key_exists('contextMenu', $context) ? $context['contextMenu'] : (function () {
            throw new RuntimeError('Variable "contextMenu" does not exist.', 146, $this->source);
        })())) {
            // line 147
            echo '                        <div id="context-menu-container" class="context-menu-container">
                            ';
            // line 148
            echo isset($context['contextMenu']) || array_key_exists('contextMenu', $context) ? $context['contextMenu'] : (function () {
                throw new RuntimeError('Variable "contextMenu" does not exist.', 148, $this->source);
            })();
            echo '
                        </div>
                    ';
        }
        // line 151
        echo '                    ';
        echo isset($context['actionMenu']) || array_key_exists('actionMenu', $context) ? $context['actionMenu'] : (function () {
            throw new RuntimeError('Variable "actionMenu" does not exist.', 151, $this->source);
        })();
        echo '
                    <div class="flex-grow"></div>
                    <button
                        type="button"
                        id="announcements-btn"
                        class="btn hidden"
                        title="';
        // line 157
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('What’s New', 'app'), 'html', null, true);
        echo '"
                    >
                        <span class="visually-hidden">';
        // line 159
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('What’s New', 'app'), 'html', null, true);
        echo '</span>
                        ';
        // line 160
        echo craft\helpers\Cp::iconSvg('gift');
        echo '
                    </button>

                    ';
        // line 164
        echo '                    <div class="account-toggle-wrapper">
                        <button
                            id="user-info"
                            aria-controls="account-menu"
                            class="btn menu-toggle"
                            aria-label="';
        // line 169
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('My Account', 'app'), 'html', null, true);
        echo '"
                            title="';
        // line 170
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('My Account', 'app'), 'html', null, true);
        echo '"
                            data-disclosure-trigger
                        >
                            ';
        // line 173
        echo isset($context['userPhoto']) || array_key_exists('userPhoto', $context) ? $context['userPhoto'] : (function () {
            throw new RuntimeError('Variable "userPhoto" does not exist.', 173, $this->source);
        })();
        echo '
                        </button>
                        <div
                            id="account-menu"
                            class="menu menu--disclosure"
                            data-align="right"
                            data-align-to=".header-photo"
                        >
                            <ul>
                                <li>
                                    <a href="';
        // line 183
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('myaccount'), 'html', null, true);
        echo '" class="flex flex-nowrap">
                                        ';
        // line 184
        if (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 184, $this->source);
        })()), 'photoId', [])) {
            // line 185
            echo '                                            ';
            echo isset($context['userPhoto']) || array_key_exists('userPhoto', $context) ? $context['userPhoto'] : (function () {
                throw new RuntimeError('Variable "userPhoto" does not exist.', 185, $this->source);
            })();
            echo '
                                        ';
        }
        // line 187
        echo '                                        <div class="flex-grow">
                                            <div>';
        // line 188
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 188, $this->source);
        })()), 'username', []), 'html', null, true);
        echo '</div>
                                            ';
        // line 189
        if (! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 189, $this->source);
        })()), 'app', []), 'config', []), 'general', []), 'useEmailAsUsername', [])) {
            // line 190
            echo '                                                <div class="smalltext">';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                throw new RuntimeError('Variable "currentUser" does not exist.', 190, $this->source);
            })()), 'email', []), 'html', null, true);
            echo '</div>
                                            ';
        }
        // line 192
        echo '                                        </div>
                                    </a>
                                </li>
                            </ul>
                            <hr>
                            <ul>
                                <li><a href="';
        // line 198
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('logout'), 'html', null, true);
        echo '">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Sign out', 'app'), 'html', null, true);
        echo '</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div id="main-container">

                <main ';
        // line 207
        echo craft\helpers\Html::renderTagAttributes((isset($context['mainAttributes']) || array_key_exists('mainAttributes', $context) ? $context['mainAttributes'] : (function () {
            throw new RuntimeError('Variable "mainAttributes" does not exist.', 207, $this->source);
        })()));
        echo '>

                    ';
        // line 209
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 209, $this->source);
        })())) {
            // line 210
            echo '<form ';
            $this->displayBlock('mainFormAttributes', $context, $blocks);
            echo '>';
            // line 211
            echo craft\helpers\Html::csrfInput();
        }
        // line 213
        echo '
                        ';
        // line 214
        if ((isset($context['showHeader']) || array_key_exists('showHeader', $context) ? $context['showHeader'] : (function () {
            throw new RuntimeError('Variable "showHeader" does not exist.', 214, $this->source);
        })())) {
            // line 215
            echo '                            <div id="header-container">
                                <header id="header">
                                    ';
            // line 217
            $this->displayBlock('header', $context, $blocks);
            // line 237
            echo '                                </header><!-- #header -->
                            </div>
                        ';
        }
        // line 240
        echo '
                        <div id="main-content" class="';
        // line 241
        echo twig_escape_filter($this->env, twig_join_filter((isset($context['mainContentClasses']) || array_key_exists('mainContentClasses', $context) ? $context['mainContentClasses'] : (function () {
            throw new RuntimeError('Variable "mainContentClasses" does not exist.', 241, $this->source);
        })()), ' '), 'html', null, true);
        echo '">
                            ';
        // line 243
        echo '                            ';
        if ((isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
            throw new RuntimeError('Variable "sidebar" does not exist.', 243, $this->source);
        })())) {
            // line 244
            echo '                                <div id="sidebar-toggle-container">
                                    <button
                                        type="button"
                                        id="sidebar-toggle"
                                        class="btn menubtn"
                                        aria-controls="sidebar-container"
                                        aria-expanded="false"
                                    >
                                        ';
            // line 252
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Show sidebar', 'app'), 'html', null, true);
            echo '
                                    </button>
                                </div>
                                <div id="sidebar-container">
                                    <div id="sidebar" class="sidebar">
                                        ';
            // line 257
            echo isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
                throw new RuntimeError('Variable "sidebar" does not exist.', 257, $this->source);
            })();
            echo '
                                    </div>
                                </div>
                            ';
        }
        // line 261
        echo '
                            ';
        // line 263
        echo '                            <div id="content-container">
                                ';
        // line 264
        if ((isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
            throw new RuntimeError('Variable "sidebar" does not exist.', 264, $this->source);
        })())) {
            // line 265
            echo '                                    <h2 id="content-heading"></h2>
                                ';
        }
        // line 267
        echo '                                ';
        $this->displayBlock('main', $context, $blocks);
        // line 301
        echo '                            </div><!-- #content-container -->

                            ';
        // line 303
        if (! twig_test_empty((isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
            throw new RuntimeError('Variable "details" does not exist.', 303, $this->source);
        })()))) {
            // line 304
            echo '                                <div id="details-container">
                                    <div id="details">
                                        <div class="details">
                                            ';
            // line 307
            echo isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
                throw new RuntimeError('Variable "details" does not exist.', 307, $this->source);
            })();
            echo '
                                        </div>
                                    </div>
                                </div>
                            ';
        }
        // line 312
        echo '                        </div><!-- #main-content -->

                        ';
        // line 314
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 314, $this->source);
        })())) {
            // line 315
            echo '</form><!-- #main-form -->';
        }
        // line 317
        echo '                </main><!-- #main -->
            </div><!-- #main-container -->

            <footer id="global-footer">
                ';
        // line 321
        if ((isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
            throw new RuntimeError('Variable "trialInfo" does not exist.', 321, $this->source);
        })())) {
            // line 322
            echo '                    <div id="trial-info" class="readable">
                        <span>
                            ';
            // line 324
            if ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                throw new RuntimeError('Variable "trialInfo" does not exist.', 324, $this->source);
            })()), 'hasCraftTrial', []) && craft\helpers\Template::attribute($this->env, $this->source, (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                throw new RuntimeError('Variable "trialInfo" does not exist.', 324, $this->source);
            })()), 'trialPluginCount', []))) {
                // line 325
                echo '                                ';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Craft Pro and {trialPluginCount, plural, =1{{name}} other{# plugins}} are installed as trials.', 'app', ['trialPluginCount' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 326
                    (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                        throw new RuntimeError('Variable "trialInfo" does not exist.', 326, $this->source);
                    })()), 'trialPluginCount', []), 'name' => twig_first($this->env, craft\helpers\Template::attribute($this->env, $this->source,                 // line 327
                        (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                            throw new RuntimeError('Variable "trialInfo" does not exist.', 327, $this->source);
                        })()), 'trialPluginNames', []))]), 'html', null, true);
                // line 328
                echo '
                            ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 329
                (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 329, $this->source);
                })()), 'hasCraftTrial', [])) {
                // line 330
                echo '                                ';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Craft Pro is installed as a trial.', 'app'), 'html', null, true);
                echo '
                            ';
            } else {
                // line 332
                echo '                                ';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('{trialPluginCount, plural, =1{{name} is installed as a trial} other{# plugins are installed as trials}}.', 'app', ['trialPluginCount' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 333
                    (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                        throw new RuntimeError('Variable "trialInfo" does not exist.', 333, $this->source);
                    })()), 'trialPluginCount', []), 'name' => twig_first($this->env, craft\helpers\Template::attribute($this->env, $this->source,                 // line 334
                        (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                            throw new RuntimeError('Variable "trialInfo" does not exist.', 334, $this->source);
                        })()), 'trialPluginNames', []))]), 'html', null, true);
                // line 335
                echo '
                            ';
            }
            // line 337
            echo '                            ';
            $context['linkText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Purchase {total, plural, =1{license} other{licenses}}', 'app', ['total' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 338
                (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 338, $this->source);
                })()), 'hasCraftTrial', [])) ? (1) : (0)) + craft\helpers\Template::attribute($this->env, $this->source, (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 338, $this->source);
                })()), 'trialPluginCount', []))]);
            // line 340
            echo '                            ';
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['class' => 'go', 'href' => craft\helpers\Template::attribute($this->env, $this->source,             // line 342
                (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 342, $this->source);
                })()), 'cartUrl', []), 'target' => '_blank', 'text' =>             // line 344
(isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
    throw new RuntimeError('Variable "linkText" does not exist.', 344, $this->source);
})()), 'aria' => ['label' =>             // line 345
(isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
    throw new RuntimeError('Variable "linkText" does not exist.', 345, $this->source);
})()), ], ]);
            // line 346
            echo '
                        </span>
                    </div>
                ';
        }
        // line 350
        echo '                <div id="app-info">
                    ';
        // line 351
        $context['fullEditionName'] = $this->extensions['craft\web\twig\Extension']->translateFilter('{edition} edition', 'app', ['edition' => (isset($context['editionName']) || array_key_exists('editionName', $context) ? $context['editionName'] : (function () {
            throw new RuntimeError('Variable "editionName" does not exist.', 351, $this->source);
        })())]);
        // line 352
        echo '                    <span>
                        Craft CMS
                        <span id="edition-logo" title="';
        // line 354
        echo twig_escape_filter($this->env, (isset($context['fullEditionName']) || array_key_exists('fullEditionName', $context) ? $context['fullEditionName'] : (function () {
            throw new RuntimeError('Variable "fullEditionName" does not exist.', 354, $this->source);
        })()), 'html', null, true);
        echo '">
                            <span aria-hidden="true">';
        // line 355
        echo twig_escape_filter($this->env, (isset($context['editionName']) || array_key_exists('editionName', $context) ? $context['editionName'] : (function () {
            throw new RuntimeError('Variable "editionName" does not exist.', 355, $this->source);
        })()), 'html', null, true);
        echo '</span>
                            <span class="visually-hidden">';
        // line 356
        echo twig_escape_filter($this->env, (isset($context['fullEditionName']) || array_key_exists('fullEditionName', $context) ? $context['fullEditionName'] : (function () {
            throw new RuntimeError('Variable "fullEditionName" does not exist.', 356, $this->source);
        })()), 'html', null, true);
        echo '</span>
                        </span>
                        ';
        // line 358
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 358, $this->source);
        })()), 'app', []), 'version', []), 'html', null, true);
        echo '
                    </span>
                    ';
        // line 360
        if (((isset($context['canUpgradeEdition']) || array_key_exists('canUpgradeEdition', $context) ? $context['canUpgradeEdition'] : (function () {
            throw new RuntimeError('Variable "canUpgradeEdition" does not exist.', 360, $this->source);
        })()) && ! (isset($context['isTrial']) || array_key_exists('isTrial', $context) ? $context['isTrial'] : (function () {
            throw new RuntimeError('Variable "isTrial" does not exist.', 360, $this->source);
        })()))) {
            // line 361
            echo '                        ';
            $context['linkText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Upgrade to Craft Pro', 'app');
            // line 362
            echo '                        <span>
                            <a
                                class="go"
                                href="';
            // line 365
            echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('plugin-store/upgrade-craft'), 'html', null, true);
            echo '"
                                aria-label="';
            // line 366
            echo twig_escape_filter($this->env, (isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
                throw new RuntimeError('Variable "linkText" does not exist.', 366, $this->source);
            })()), 'html', null, true);
            echo '"
                            >';
            // line 367
            echo twig_escape_filter($this->env, (isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
                throw new RuntimeError('Variable "linkText" does not exist.', 367, $this->source);
            })()), 'html', null, true);
            echo '</a>
                        </span>
                    ';
        }
        // line 370
        echo '                </div>
            </footer>

        </div><!-- #page-container -->
    </div><!-- #global-container -->
';
        craft\helpers\Template::endProfile('block', 'body');
    }

    // line 210
    public function block_mainFormAttributes($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'mainFormAttributes');
        echo craft\helpers\Html::renderTagAttributes((isset($context['mainFormAttributes']) || array_key_exists('mainFormAttributes', $context) ? $context['mainFormAttributes'] : (function () {
            throw new RuntimeError('Variable "mainFormAttributes" does not exist.', 210, $this->source);
        })()));
        craft\helpers\Template::endProfile('block', 'mainFormAttributes');
    }

    // line 217
    public function block_header($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'header');
        // line 218
        echo '                                        <div id="page-title" class="flex';
        if ((isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
            throw new RuntimeError('Variable "toolbar" does not exist.', 218, $this->source);
        })())) {
            echo ' has-toolbar';
        }
        echo '">
                                            ';
        // line 219
        $this->displayBlock('pageTitle', $context, $blocks);
        // line 224
        echo '                                        </div>
                                        ';
        // line 225
        if ((isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
            throw new RuntimeError('Variable "toolbar" does not exist.', 225, $this->source);
        })())) {
            // line 226
            echo '                                            <div id="toolbar" class="flex">
                                                ';
            // line 227
            echo isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
                throw new RuntimeError('Variable "toolbar" does not exist.', 227, $this->source);
            })();
            echo '
                                            </div>
                                        ';
        }
        // line 230
        echo '                                        ';
        if (((isset($context['actionButton']) || array_key_exists('actionButton', $context) ? $context['actionButton'] : (function () {
            throw new RuntimeError('Variable "actionButton" does not exist.', 230, $this->source);
        })()) || (isset($context['additionalButtons']) || array_key_exists('additionalButtons', $context) ? $context['additionalButtons'] : (function () {
            throw new RuntimeError('Variable "additionalButtons" does not exist.', 230, $this->source);
        })()))) {
            // line 231
            echo '                                            <div id="action-buttons" class="flex">
                                                ';
            // line 232
            echo isset($context['additionalButtons']) || array_key_exists('additionalButtons', $context) ? $context['additionalButtons'] : (function () {
                throw new RuntimeError('Variable "additionalButtons" does not exist.', 232, $this->source);
            })();
            echo '
                                                ';
            // line 233
            echo isset($context['actionButton']) || array_key_exists('actionButton', $context) ? $context['actionButton'] : (function () {
                throw new RuntimeError('Variable "actionButton" does not exist.', 233, $this->source);
            })();
            echo '
                                            </div>
                                        ';
        }
        // line 236
        echo '                                    ';
        craft\helpers\Template::endProfile('block', 'header');
    }

    // line 219
    public function block_pageTitle($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'pageTitle');
        // line 220
        echo '                                                ';
        if ((array_key_exists('title', $context) && $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
            throw new RuntimeError('Variable "title" does not exist.', 220, $this->source);
        })())))) {
            // line 221
            echo '                                                    <h1 class="screen-title" title="';
            echo twig_escape_filter($this->env, (isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
                throw new RuntimeError('Variable "title" does not exist.', 221, $this->source);
            })()), 'html', null, true);
            echo '">';
            echo twig_escape_filter($this->env, (isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
                throw new RuntimeError('Variable "title" does not exist.', 221, $this->source);
            })()), 'html', null, true);
            echo '</h1>
                                                ';
        }
        // line 223
        echo '                                            ';
        craft\helpers\Template::endProfile('block', 'pageTitle');
    }

    // line 267
    public function block_main($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'main');
        // line 268
        echo '                                    ';
        if (! twig_test_empty((isset($context['errorSummary']) || array_key_exists('errorSummary', $context) ? $context['errorSummary'] : (function () {
            throw new RuntimeError('Variable "errorSummary" does not exist.', 268, $this->source);
        })()))) {
            // line 269
            echo '                                        ';
            echo (array_key_exists('errorSummary', $context)) ? ((isset($context['errorSummary']) || array_key_exists('errorSummary', $context) ? $context['errorSummary'] : (function () {
                throw new RuntimeError('Variable "errorSummary" does not exist.', 269, $this->source);
            })())) : ('');
            echo '
                                    ';
        }
        // line 271
        echo '                                    <div id="content" class="content-pane">
                                        ';
        // line 272
        if (((isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
            throw new RuntimeError('Variable "contentNotice" does not exist.', 272, $this->source);
        })()) || (isset($context['tabs']) || array_key_exists('tabs', $context) ? $context['tabs'] : (function () {
            throw new RuntimeError('Variable "tabs" does not exist.', 272, $this->source);
        })()))) {
            // line 273
            echo '                                            <header id="content-header" class="pane-header">
                                                ';
            // line 274
            echo ((isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
                throw new RuntimeError('Variable "contentNotice" does not exist.', 274, $this->source);
            })())) ? ($this->extensions['craft\web\twig\Extension']->tagFunction('div', ['id' => 'content-notice', 'html' =>             // line 276
(isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
    throw new RuntimeError('Variable "contentNotice" does not exist.', 276, $this->source);
})()), 'role' => 'status', ])) : ('');
            // line 278
            echo '
                                                ';
            // line 279
            if ((isset($context['tabs']) || array_key_exists('tabs', $context) ? $context['tabs'] : (function () {
                throw new RuntimeError('Variable "tabs" does not exist.', 279, $this->source);
            })())) {
                // line 280
                echo '                                                    ';
                $this->loadTemplate('_includes/tabs', '_layouts/cp', 280)->display(twig_array_merge($context, ['containerAttributes' => ['id' => 'tabs']]));
                // line 285
                echo '                                                ';
            }
            // line 286
            echo '                                            </header>
                                        ';
        }
        // line 288
        echo '
                                        ';
        // line 289
        $this->displayBlock('content', $context, $blocks);
        // line 292
        echo '
                                        ';
        // line 294
        echo '                                        ';
        if ((isset($context['footer']) || array_key_exists('footer', $context) ? $context['footer'] : (function () {
            throw new RuntimeError('Variable "footer" does not exist.', 294, $this->source);
        })())) {
            // line 295
            echo '                                            <div id="footer" class="flex flex-justify">
                                                ';
            // line 296
            echo isset($context['footer']) || array_key_exists('footer', $context) ? $context['footer'] : (function () {
                throw new RuntimeError('Variable "footer" does not exist.', 296, $this->source);
            })();
            echo '
                                            </div>
                                        ';
        }
        // line 299
        echo '                                    </div>
                                ';
        craft\helpers\Template::endProfile('block', 'main');
    }

    // line 289
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 290
        echo '                                            ';
        echo (array_key_exists('content', $context)) ? ((isset($context['content']) || array_key_exists('content', $context) ? $context['content'] : (function () {
            throw new RuntimeError('Variable "content" does not exist.', 290, $this->source);
        })())) : ('');
        echo '
                                        ';
        craft\helpers\Template::endProfile('block', 'content');
    }

    // line 378
    public function block_actionButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 379
        echo '    ';
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 379, $this->source);
        })())) {
            // line 380
            echo '        <div class="btngroup">
            ';
            // line 381
            $this->displayBlock('submitButton', $context, $blocks);
            // line 384
            echo '            ';
            if ((($context['formActions']) ?? (false))) {
                // line 385
                echo '                <button
                    type="button"
                    class="btn submit menubtn"
                    aria-label="';
                // line 388
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('More actions', 'app'), 'html', null, true);
                echo '"
                    aria-controls="form-action-menu"
                    data-disclosure-trigger
                ></button>
                ';
                // line 392
                $this->loadTemplate('_layouts/components/form-action-menu', '_layouts/cp', 392)->display($context);
                // line 393
                echo '            ';
            }
            // line 394
            echo '        </div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'actionButton');
    }

    // line 381
    public function block_submitButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'submitButton');
        // line 382
        echo '                <button type="submit" class="btn submit">';
        echo twig_escape_filter($this->env, (($context['submitButtonLabel']) ?? ($this->extensions['craft\web\twig\Extension']->translateFilter('Save', 'app'))), 'html', null, true);
        echo '</button>
            ';
        craft\helpers\Template::endProfile('block', 'submitButton');
    }

    public function getTemplateName()
    {
        return '_layouts/cp';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [750 => 382,  745 => 381,  738 => 394,  735 => 393,  733 => 392,  726 => 388,  721 => 385,  718 => 384,  716 => 381,  713 => 380,  710 => 379,  705 => 378,  697 => 290,  692 => 289,  686 => 299,  680 => 296,  677 => 295,  674 => 294,  671 => 292,  669 => 289,  666 => 288,  662 => 286,  659 => 285,  656 => 280,  654 => 279,  651 => 278,  649 => 276,  648 => 274,  645 => 273,  643 => 272,  640 => 271,  634 => 269,  631 => 268,  626 => 267,  621 => 223,  613 => 221,  610 => 220,  605 => 219,  600 => 236,  594 => 233,  590 => 232,  587 => 231,  584 => 230,  578 => 227,  575 => 226,  573 => 225,  570 => 224,  568 => 219,  561 => 218,  556 => 217,  547 => 210,  537 => 370,  531 => 367,  527 => 366,  523 => 365,  518 => 362,  515 => 361,  513 => 360,  508 => 358,  503 => 356,  499 => 355,  495 => 354,  491 => 352,  489 => 351,  486 => 350,  480 => 346,  478 => 345,  477 => 344,  476 => 342,  474 => 340,  472 => 338,  470 => 337,  466 => 335,  464 => 334,  463 => 333,  461 => 332,  455 => 330,  453 => 329,  450 => 328,  448 => 327,  447 => 326,  445 => 325,  443 => 324,  439 => 322,  437 => 321,  431 => 317,  428 => 315,  426 => 314,  422 => 312,  414 => 307,  409 => 304,  407 => 303,  403 => 301,  400 => 267,  396 => 265,  394 => 264,  391 => 263,  388 => 261,  381 => 257,  373 => 252,  363 => 244,  360 => 243,  356 => 241,  353 => 240,  348 => 237,  346 => 217,  342 => 215,  340 => 214,  337 => 213,  334 => 211,  330 => 210,  328 => 209,  323 => 207,  309 => 198,  301 => 192,  295 => 190,  293 => 189,  289 => 188,  286 => 187,  280 => 185,  278 => 184,  274 => 183,  261 => 173,  255 => 170,  251 => 169,  244 => 164,  238 => 160,  234 => 159,  229 => 157,  219 => 151,  213 => 148,  210 => 147,  207 => 146,  205 => 145,  199 => 142,  196 => 141,  194 => 140,  190 => 138,  188 => 137,  183 => 134,  180 => 129,  175 => 128,  169 => 42,  164 => 401,  162 => 400,  160 => 399,  154 => 123,  152 => 122,  150 => 120,  148 => 118,  147 => 116,  146 => 115,  145 => 114,  144 => 113,  143 => 111,  142 => 110,  141 => 109,  140 => 108,  139 => 102,  137 => 100,  135 => 98,  134 => 95,  131 => 92,  129 => 91,  126 => 89,  124 => 88,  122 => 87,  120 => 86,  118 => 83,  117 => 82,  116 => 81,  114 => 79,  112 => 78,  110 => 77,  108 => 76,  106 => 75,  104 => 74,  102 => 73,  100 => 72,  98 => 71,  96 => 70,  94 => 69,  92 => 68,  90 => 66,  88 => 65,  86 => 64,  84 => 63,  82 => 62,  80 => 60,  78 => 59,  73 => 55,  68 => 52,  66 => 51,  63 => 50,  61 => 49,  56 => 48,  54 => 47,  52 => 46,  50 => 45,  42 => 42];
    }

    public function getSourceContext()
    {
        return new Source("{#
┌────────────────────────────────────────────────────────────────────────────────────┐
│                                 #global-container                                  │
│   ┌─────┐   ┌──────────────────────────────────────────────────────────────────┐   │
│   │     │   │                         #page-container                          │   │
│   │     │   │   ┌──────────────────────────────────────────────────────────┐   │   │
│   │     │   │   │                      #global-header                      │   │   │
│   │     │   │   └──────────────────────────────────────────────────────────┘   │   │
│   │     │   │                                                                  │   │
│   │     │   │   ┌──────────────────────────────────────────────────────────┐   │   │
│   │     │   │   │                          #main                           │   │   │
│   │  #  │   │   │   ┌──────────────────────────────────────────────────┐   │   │   │
│   │  g  │   │   │   │                #header-container                 │   │   │   │
│   │  l  │   │   │   └──────────────────────────────────────────────────┘   │   │   │
│   │  o  │   │   │                                                          │   │   │
│   │  b  │   │   │   ┌──────────────────────────────────────────────────┐   │   │   │
│   │  a  │   │   │   │                  #main-content                   │   │   │   │
│   │  l  │   │   │   │   ┌─────┐   ┌──────────────────────┐   ┌─────┐   │   │   │   │
│   │  -  │   │   │   │   │     │   │                      │   │     │   │   │   │   │
│   │  s  │   │   │   │   │  #  │   │                      │   │  #  │   │   │   │   │
│   │  i  │   │   │   │   │  s  │   │                      │   │  d  │   │   │   │   │
│   │  d  │   │   │   │   │  i  │   │                      │   │  e  │   │   │   │   │
│   │  e  │   │   │   │   │  d  │   │       #content       │   │  t  │   │   │   │   │
│   │  b  │   │   │   │   │  e  │   │                      │   │  a  │   │   │   │   │
│   │  a  │   │   │   │   │  b  │   │                      │   │  i  │   │   │   │   │
│   │  r  │   │   │   │   │  a  │   │                      │   │  l  │   │   │   │   │
│   │     │   │   │   │   │  r  │   │                      │   │  s  │   │   │   │   │
│   │     │   │   │   │   │     │   │                      │   │     │   │   │   │   │
│   │     │   │   │   │   └─────┘   └──────────────────────┘   └─────┘   │   │   │   │
│   │     │   │   │   │                                                  │   │   │   │
│   │     │   │   │   └──────────────────────────────────────────────────┘   │   │   │
│   │     │   │   │                                                          │   │   │
│   │     │   │   └──────────────────────────────────────────────────────────┘   │   │
│   │     │   │   ┌──────────────────────────────────────────────────────────┐   │   │
│   │     │   │   │                      #global-footer                      │   │   │
│   │     │   │   └──────────────────────────────────────────────────────────┘   │   │
│   └─────┘   └──────────────────────────────────────────────────────────────────┘   │
│                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────┘
#}

{% extends '_layouts/basecp.twig' %}

{# The control panel only supports queue components that implement QueueInterface #}
{% set queue = craft.app.queue %}
{% js %}
{% if queue is instance of(\"craft\\\\queue\\\\QueueInterface\") %}
    Craft.cp.setJobInfo({{ queue.getJobInfo(100)|json_encode|raw }}, false);
    {% if queue.getHasReservedJobs() %}
        Craft.cp.trackJobProgress(true);
    {% elseif queue.getHasWaitingJobs() %}
        Craft.cp.runQueue();
    {% endif %}
{% else %}
    Craft.cp.enableQueue = false;
{% endif %}
{% endjs %}

{% set hasSystemIcon = CraftEdition == CraftPro and craft.rebrand.isIconUploaded %}
{% set fullPageForm = (fullPageForm is defined and fullPageForm) %}

{% set editionName = craft.app.getEditionName() %}
{% set canUpgradeEdition = craft.app.getCanUpgradeEdition() %}
{% set licensedEdition = craft.app.getLicensedEdition() %}
{% set isTrial = licensedEdition is not same as(null) and licensedEdition is not same as(CraftEdition) %}
{% set trialInfo = craft.cp.trialInfo() %}

{% set contentNotice = (contentNotice ?? block('contentNotice') ?? '')|trim %}
{% set sidebar = (sidebar ?? block('sidebar') ?? '')|trim %}
{% set toolbar = (toolbar ?? block('toolbar') ?? '')|trim %}
{% set actionButton = (block('actionButton') ?? '')|trim %}
{% set additionalButtons = additionalButtons ?? null %}
{% set details = (details ?? block('details') ?? '')|trim %}
{% set footer = (footer ?? block('footer') ?? '')|trim %}
{% set crumbs = crumbs ?? null %}
{% set contextMenu = (contextMenu ?? block('contextMenu') ?? '')|trim %}
{% set actionMenu = actionMenu ?? '' %}
{% set tabs = (tabs ?? [])|length > 1 ? tabs : null %}
{% set errorSummary = errorSummary ?? null %}

{% set mainContentClasses = [
    sidebar ? 'has-sidebar',
    details ? 'has-details',
]|filter %}

{% set bodyClass = (bodyClass ?? [])|explodeClass %}
{% set showHeader = showHeader ?? true %}
{% if not showHeader %}
    {% set bodyClass = bodyClass|push('no-header') -%}
{% endif %}
{% if craft.app.hasModule('debug') %}
    {% set bodyClass = bodyClass|push('has-debug-toolbar') %}
{% endif %}

{% set mainAttributes = {
    id: 'main',
    role: 'main',
}|merge(mainAttributes ?? []) %}

{% set formActions = craft.cp.prepFormActions(formActions ?? null) %}

{% set mainFormAttributes = {
    id: 'main-form',
    method: 'post',
    'accept-charset': 'UTF-8',
    novalidate: true,
    data: {
        saveshortcut: saveShortcut ?? true,
        'saveshortcut-redirect': (saveShortcutRedirect ?? false) ? saveShortcutRedirect|hash : false,
        'saveshortcut-scroll': retainScrollOnSaveShortcut ?? false,
        actions: formActions ?? false,
        'confirm-unload': true,
        delta: view.getIsDeltaRegistrationActive(),
        'delta-names': view.getDeltaNames(),
        'initial-delta-values': view.getInitialDeltaValues(),
        'modified-delta-names': craft.app.request.getBodyParam('modifiedDeltaNames') ?? [],
    },
}|merge(mainFormAttributes ?? [], recursive=true) %}

{% set userPhoto = include('_layouts/components/header-photo.twig') %}

{% js at head %}
// Remove the hash so the browser doesn't scroll to it
window.LOCATION_HASH = document.location.hash ? decodeURIComponent(document.location.hash.substr(1)) : null;
history.replaceState(undefined, undefined, window.location.href.match(/^[^#]*/)[0]);
{% endjs %}

{% block body %}
    {{ tag ('a', {
        id: 'global-skip-link',
        text: 'Skip to content'|t('app'),
        href: '#main',
        class: 'skip-link btn',
    }) }}

    <div id=\"global-container\">
        {% include '_layouts/components/global-sidebar' %}

        <div id=\"page-container\">
            {% include '_layouts/components/alerts' %}

            <div id=\"global-header\" role=\"region\" aria-label=\"{{ 'My Account'|t('app') }}\">

                <div class=\"flex flex-nowrap gap-xs\">
                    {% include '_layouts/components/crumbs' %}
                    {% if contextMenu %}
                        <div id=\"context-menu-container\" class=\"context-menu-container\">
                            {{ contextMenu|raw }}
                        </div>
                    {% endif %}
                    {{ actionMenu|raw }}
                    <div class=\"flex-grow\"></div>
                    <button
                        type=\"button\"
                        id=\"announcements-btn\"
                        class=\"btn hidden\"
                        title=\"{{ 'What’s New'|t('app') }}\"
                    >
                        <span class=\"visually-hidden\">{{ 'What’s New'|t('app') }}</span>
                        {{ iconSvg('gift') }}
                    </button>

                    {# New account dropdown #}
                    <div class=\"account-toggle-wrapper\">
                        <button
                            id=\"user-info\"
                            aria-controls=\"account-menu\"
                            class=\"btn menu-toggle\"
                            aria-label=\"{{ 'My Account'|t('app') }}\"
                            title=\"{{ 'My Account'|t('app') }}\"
                            data-disclosure-trigger
                        >
                            {{ userPhoto|raw }}
                        </button>
                        <div
                            id=\"account-menu\"
                            class=\"menu menu--disclosure\"
                            data-align=\"right\"
                            data-align-to=\".header-photo\"
                        >
                            <ul>
                                <li>
                                    <a href=\"{{ url('myaccount') }}\" class=\"flex flex-nowrap\">
                                        {% if currentUser.photoId %}
                                            {{ userPhoto|raw }}
                                        {% endif %}
                                        <div class=\"flex-grow\">
                                            <div>{{ currentUser.username }}</div>
                                            {% if not craft.app.config.general.useEmailAsUsername %}
                                                <div class=\"smalltext\">{{ currentUser.email }}</div>
                                            {% endif %}
                                        </div>
                                    </a>
                                </li>
                            </ul>
                            <hr>
                            <ul>
                                <li><a href=\"{{ url('logout') }}\">{{ \"Sign out\"|t('app') }}</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div id=\"main-container\">

                <main {{ attr(mainAttributes) }}>

                    {% if fullPageForm -%}
                    <form {% block mainFormAttributes %}{{ attr(mainFormAttributes) }}{% endblock %}>
                        {{- csrfInput() }}
                        {%- endif %}

                        {% if showHeader %}
                            <div id=\"header-container\">
                                <header id=\"header\">
                                    {% block header %}
                                        <div id=\"page-title\" class=\"flex{% if toolbar %} has-toolbar{% endif %}\">
                                            {% block pageTitle %}
                                                {% if title is defined and title|length %}
                                                    <h1 class=\"screen-title\" title=\"{{ title }}\">{{ title }}</h1>
                                                {% endif %}
                                            {% endblock %}
                                        </div>
                                        {% if toolbar %}
                                            <div id=\"toolbar\" class=\"flex\">
                                                {{ toolbar|raw }}
                                            </div>
                                        {% endif %}
                                        {% if actionButton or additionalButtons %}
                                            <div id=\"action-buttons\" class=\"flex\">
                                                {{ additionalButtons|raw }}
                                                {{ actionButton|raw }}
                                            </div>
                                        {% endif %}
                                    {% endblock %}
                                </header><!-- #header -->
                            </div>
                        {% endif %}

                        <div id=\"main-content\" class=\"{{ mainContentClasses|join(' ') }}\">
                            {# sidebar #}
                            {% if sidebar %}
                                <div id=\"sidebar-toggle-container\">
                                    <button
                                        type=\"button\"
                                        id=\"sidebar-toggle\"
                                        class=\"btn menubtn\"
                                        aria-controls=\"sidebar-container\"
                                        aria-expanded=\"false\"
                                    >
                                        {{ 'Show sidebar'|t('app') }}
                                    </button>
                                </div>
                                <div id=\"sidebar-container\">
                                    <div id=\"sidebar\" class=\"sidebar\">
                                        {{ sidebar|raw }}
                                    </div>
                                </div>
                            {% endif %}

                            {# content-container #}
                            <div id=\"content-container\">
                                {% if sidebar %}
                                    <h2 id=\"content-heading\"></h2>
                                {% endif %}
                                {% block main %}
                                    {% if errorSummary is not empty %}
                                        {{ errorSummary is defined ? errorSummary|raw }}
                                    {% endif %}
                                    <div id=\"content\" class=\"content-pane\">
                                        {% if contentNotice or tabs %}
                                            <header id=\"content-header\" class=\"pane-header\">
                                                {{ contentNotice ? tag('div', {
                                                    id: 'content-notice',
                                                    html: contentNotice,
                                                    role: 'status',
                                                }) }}
                                                {% if tabs %}
                                                    {% include \"_includes/tabs\" with {
                                                        containerAttributes: {
                                                            id: 'tabs',
                                                        },
                                                    } %}
                                                {% endif %}
                                            </header>
                                        {% endif %}

                                        {% block content %}
                                            {{ content is defined ? content|raw }}
                                        {% endblock %}

                                        {# footer #}
                                        {% if footer %}
                                            <div id=\"footer\" class=\"flex flex-justify\">
                                                {{ footer|raw }}
                                            </div>
                                        {% endif %}
                                    </div>
                                {% endblock %}
                            </div><!-- #content-container -->

                            {% if details is not empty %}
                                <div id=\"details-container\">
                                    <div id=\"details\">
                                        <div class=\"details\">
                                            {{ details|raw }}
                                        </div>
                                    </div>
                                </div>
                            {% endif %}
                        </div><!-- #main-content -->

                        {% if fullPageForm -%}
                    </form><!-- #main-form -->
                    {%- endif %}
                </main><!-- #main -->
            </div><!-- #main-container -->

            <footer id=\"global-footer\">
                {% if trialInfo %}
                    <div id=\"trial-info\" class=\"readable\">
                        <span>
                            {% if trialInfo.hasCraftTrial and trialInfo.trialPluginCount %}
                                {{ 'Craft Pro and {trialPluginCount, plural, =1{{name}} other{# plugins}} are installed as trials.'|t('app', {
                                    trialPluginCount: trialInfo.trialPluginCount,
                                    name: trialInfo.trialPluginNames|first,
                                }) }}
                            {% elseif trialInfo.hasCraftTrial %}
                                {{ 'Craft Pro is installed as a trial.'|t('app') }}
                            {% else %}
                                {{ '{trialPluginCount, plural, =1{{name} is installed as a trial} other{# plugins are installed as trials}}.'|t('app', {
                                    trialPluginCount: trialInfo.trialPluginCount,
                                    name: trialInfo.trialPluginNames|first,
                                }) }}
                            {% endif %}
                            {% set linkText = 'Purchase {total, plural, =1{license} other{licenses}}'|t('app', {
                                total: (trialInfo.hasCraftTrial ? 1 : 0) + trialInfo.trialPluginCount,
                            }) %}
                            {{ tag('a', {
                                class: 'go',
                                href: trialInfo.cartUrl,
                                target: '_blank',
                                text: linkText,
                                aria: {label: linkText},
                            }) }}
                        </span>
                    </div>
                {% endif %}
                <div id=\"app-info\">
                    {% set fullEditionName = '{edition} edition'|t('app', {edition: editionName}) %}
                    <span>
                        Craft CMS
                        <span id=\"edition-logo\" title=\"{{ fullEditionName }}\">
                            <span aria-hidden=\"true\">{{ editionName }}</span>
                            <span class=\"visually-hidden\">{{ fullEditionName }}</span>
                        </span>
                        {{ craft.app.version }}
                    </span>
                    {% if canUpgradeEdition and not isTrial %}
                        {% set linkText = 'Upgrade to Craft Pro'|t('app') %}
                        <span>
                            <a
                                class=\"go\"
                                href=\"{{ url('plugin-store/upgrade-craft') }}\"
                                aria-label=\"{{ linkText }}\"
                            >{{ linkText }}</a>
                        </span>
                    {% endif %}
                </div>
            </footer>

        </div><!-- #page-container -->
    </div><!-- #global-container -->
{% endblock %}


{% block actionButton %}
    {% if fullPageForm %}
        <div class=\"btngroup\">
            {% block submitButton %}
                <button type=\"submit\" class=\"btn submit\">{{ submitButtonLabel ?? 'Save'|t('app') }}</button>
            {% endblock %}
            {% if formActions ?? false %}
                <button
                    type=\"button\"
                    class=\"btn submit menubtn\"
                    aria-label=\"{{ 'More actions'|t('app') }}\"
                    aria-controls=\"form-action-menu\"
                    data-disclosure-trigger
                ></button>
                {% include '_layouts/components/form-action-menu' %}
            {% endif %}
        </div>
    {% endif %}
{% endblock %}


{% if currentUser.can('performUpdates') and not craft.app.updates.getIsUpdateInfoCached() %}
    {% js %}
    Craft.cp.checkForUpdates();
    {% endjs %}
{% endif %}
", '_layouts/cp', '/Users/brianhanson/Development/craft5/src/templates/_layouts/cp.twig');
    }
}
