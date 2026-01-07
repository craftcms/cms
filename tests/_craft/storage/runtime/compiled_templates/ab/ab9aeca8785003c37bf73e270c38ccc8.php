<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/cp */
class __TwigTemplate_3d8eb812980ef91d2fe2ed9979d08d03 extends Template
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
        echo '    ';
        if ($this->env->getTest('instance of')->getCallable()((isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
            throw new RuntimeError('Variable "queue" does not exist.', 47, $this->source);
        })()), 'craft\\queue\\QueueInterface')) {
            // line 48
            echo '        Craft.cp.setJobInfo(';
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
                echo '            Craft.cp.trackJobProgress(true);
        ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 51
                (isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
                    throw new RuntimeError('Variable "queue" does not exist.', 51, $this->source);
                })()), 'getHasWaitingJobs', [], 'method')) {
                // line 52
                echo '            Craft.cp.runQueue();
        ';
            }
            // line 54
            echo '    ';
        } else {
            // line 55
            echo '        Craft.cp.enableQueue = false;
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
        echo "    // Remove the hash so the browser doesn't scroll to it
    window.LOCATION_HASH = document.location.hash ? decodeURIComponent(document.location.hash.substr(1)) : null;
    history.replaceState(undefined, undefined, window.location.href.match(/^[^#]*/)[0]);
";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 1]);
        // line 370
        if ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 370, $this->source);
        })()), 'can', [0 => 'performUpdates'], 'method') && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 370, $this->source);
        })()), 'app', []), 'updates', []), 'getIsUpdateInfoCached', [], 'method'))) {
            // line 371
            ob_start();
            // line 372
            echo '        Craft.cp.checkForUpdates();
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
        // line 144
        $this->loadTemplate('_layouts/components/crumbs', '_layouts/cp', 144)->display($context);
        // line 145
        echo '                    ';
        if ((isset($context['contextMenu']) || array_key_exists('contextMenu', $context) ? $context['contextMenu'] : (function () {
            throw new RuntimeError('Variable "contextMenu" does not exist.', 145, $this->source);
        })())) {
            // line 146
            echo '                        <div id="context-menu-container" class="context-menu-container">
                            ';
            // line 147
            echo isset($context['contextMenu']) || array_key_exists('contextMenu', $context) ? $context['contextMenu'] : (function () {
                throw new RuntimeError('Variable "contextMenu" does not exist.', 147, $this->source);
            })();
            echo '
                        </div>
                    ';
        }
        // line 150
        echo '                    ';
        echo isset($context['actionMenu']) || array_key_exists('actionMenu', $context) ? $context['actionMenu'] : (function () {
            throw new RuntimeError('Variable "actionMenu" does not exist.', 150, $this->source);
        })();
        echo '
                    <div class="flex-grow"></div>
                    <button type="button" id="announcements-btn" class="btn hidden" title="';
        // line 152
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('What’s New', 'app'), 'html', null, true);
        echo '">
                        <span class="visually-hidden">';
        // line 153
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('What’s New', 'app'), 'html', null, true);
        echo '</span>
                        ';
        // line 154
        echo craft\helpers\Cp::iconSvg('gift');
        echo '
                    </button>

                    ';
        // line 158
        echo '                    <div class="account-toggle-wrapper">
                        <button
                            id="user-info"
                            aria-controls="account-menu"
                            class="btn menu-toggle"
                            aria-label="';
        // line 163
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('My Account', 'app'), 'html', null, true);
        echo '"
                            title="';
        // line 164
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('My Account', 'app'), 'html', null, true);
        echo '"
                            data-disclosure-trigger>
                            ';
        // line 166
        echo isset($context['userPhoto']) || array_key_exists('userPhoto', $context) ? $context['userPhoto'] : (function () {
            throw new RuntimeError('Variable "userPhoto" does not exist.', 166, $this->source);
        })();
        echo '
                        </button>
                        <div id="account-menu" class="menu menu--disclosure" data-align="right" data-align-to=".header-photo">
                            <ul>
                                <li>
                                    <a href="';
        // line 171
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('myaccount'), 'html', null, true);
        echo '" class="flex flex-nowrap">
                                        ';
        // line 172
        if (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 172, $this->source);
        })()), 'photoId', [])) {
            // line 173
            echo '                                            ';
            echo isset($context['userPhoto']) || array_key_exists('userPhoto', $context) ? $context['userPhoto'] : (function () {
                throw new RuntimeError('Variable "userPhoto" does not exist.', 173, $this->source);
            })();
            echo '
                                        ';
        }
        // line 175
        echo '                                        <div class="flex-grow">
                                            <div>';
        // line 176
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 176, $this->source);
        })()), 'username', []), 'html', null, true);
        echo '</div>
                                            ';
        // line 177
        if (! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 177, $this->source);
        })()), 'app', []), 'config', []), 'general', []), 'useEmailAsUsername', [])) {
            // line 178
            echo '                                                <div class="smalltext">';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                throw new RuntimeError('Variable "currentUser" does not exist.', 178, $this->source);
            })()), 'email', []), 'html', null, true);
            echo '</div>
                                            ';
        }
        // line 180
        echo '                                        </div>
                                    </a>
                                </li>
                            </ul>
                            <hr>
                            <ul>
                                <li><a href="';
        // line 186
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
        // line 194
        echo craft\helpers\Html::renderTagAttributes((isset($context['mainAttributes']) || array_key_exists('mainAttributes', $context) ? $context['mainAttributes'] : (function () {
            throw new RuntimeError('Variable "mainAttributes" does not exist.', 194, $this->source);
        })()));
        echo '>

                    ';
        // line 196
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 196, $this->source);
        })())) {
            // line 197
            echo '<form ';
            $this->displayBlock('mainFormAttributes', $context, $blocks);
            echo '>';
            // line 198
            echo craft\helpers\Html::csrfInput();
        }
        // line 200
        echo '
                    ';
        // line 201
        if ((isset($context['showHeader']) || array_key_exists('showHeader', $context) ? $context['showHeader'] : (function () {
            throw new RuntimeError('Variable "showHeader" does not exist.', 201, $this->source);
        })())) {
            // line 202
            echo '                        <div id="header-container">
                            <header id="header">
                                ';
            // line 204
            $this->displayBlock('header', $context, $blocks);
            // line 224
            echo '                            </header><!-- #header -->
                        </div>
                    ';
        }
        // line 227
        echo '
                    <div id="main-content" class="';
        // line 228
        echo twig_escape_filter($this->env, twig_join_filter((isset($context['mainContentClasses']) || array_key_exists('mainContentClasses', $context) ? $context['mainContentClasses'] : (function () {
            throw new RuntimeError('Variable "mainContentClasses" does not exist.', 228, $this->source);
        })()), ' '), 'html', null, true);
        echo '">
                        ';
        // line 230
        echo '                        ';
        if ((isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
            throw new RuntimeError('Variable "sidebar" does not exist.', 230, $this->source);
        })())) {
            // line 231
            echo '                            <div id="sidebar-toggle-container">
                                <button type="button" id="sidebar-toggle" class="btn menubtn" aria-controls="sidebar-container" aria-expanded="false">
                                    ';
            // line 233
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Show sidebar', 'app'), 'html', null, true);
            echo '
                                </button>
                            </div>
                            <div id="sidebar-container">
                                <div id="sidebar" class="sidebar">
                                    ';
            // line 238
            echo isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
                throw new RuntimeError('Variable "sidebar" does not exist.', 238, $this->source);
            })();
            echo '
                                </div>
                            </div>
                        ';
        }
        // line 242
        echo '
                        ';
        // line 244
        echo '                        <div id="content-container">
                            ';
        // line 245
        if ((isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
            throw new RuntimeError('Variable "sidebar" does not exist.', 245, $this->source);
        })())) {
            // line 246
            echo '                                <h2 id="content-heading"></h2>
                            ';
        }
        // line 248
        echo '                            ';
        $this->displayBlock('main', $context, $blocks);
        // line 282
        echo '                        </div><!-- #content-container -->

                        ';
        // line 284
        if (! twig_test_empty((isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
            throw new RuntimeError('Variable "details" does not exist.', 284, $this->source);
        })()))) {
            // line 285
            echo '                            <div id="details-container">
                                <div id="details">
                                    <div class="details">
                                        ';
            // line 288
            echo isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
                throw new RuntimeError('Variable "details" does not exist.', 288, $this->source);
            })();
            echo '
                                    </div>
                                </div>
                            </div>
                        ';
        }
        // line 293
        echo '                    </div><!-- #main-content -->

                    ';
        // line 295
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 295, $this->source);
        })())) {
            // line 296
            echo '</form><!-- #main-form -->';
        }
        // line 298
        echo '                </main><!-- #main -->
            </div><!-- #main-container -->

            <footer id="global-footer">
                ';
        // line 302
        if ((isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
            throw new RuntimeError('Variable "trialInfo" does not exist.', 302, $this->source);
        })())) {
            // line 303
            echo '                    <div id="trial-info" class="readable">
                        <span>
                            ';
            // line 305
            if ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                throw new RuntimeError('Variable "trialInfo" does not exist.', 305, $this->source);
            })()), 'hasCraftTrial', []) && craft\helpers\Template::attribute($this->env, $this->source, (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                throw new RuntimeError('Variable "trialInfo" does not exist.', 305, $this->source);
            })()), 'trialPluginCount', []))) {
                // line 306
                echo '                                ';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Craft Pro and {trialPluginCount, plural, =1{{name}} other{# plugins}} are installed as trials.', 'app', ['trialPluginCount' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 307
                    (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                        throw new RuntimeError('Variable "trialInfo" does not exist.', 307, $this->source);
                    })()), 'trialPluginCount', []), 'name' => twig_first($this->env, craft\helpers\Template::attribute($this->env, $this->source,                 // line 308
                        (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                            throw new RuntimeError('Variable "trialInfo" does not exist.', 308, $this->source);
                        })()), 'trialPluginNames', []))]), 'html', null, true);
                // line 309
                echo '
                            ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 310
                (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 310, $this->source);
                })()), 'hasCraftTrial', [])) {
                // line 311
                echo '                                ';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Craft Pro is installed as a trial.', 'app'), 'html', null, true);
                echo '
                            ';
            } else {
                // line 313
                echo '                                ';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('{trialPluginCount, plural, =1{{name} is installed as a trial} other{# plugins are installed as trials}}.', 'app', ['trialPluginCount' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 314
                    (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                        throw new RuntimeError('Variable "trialInfo" does not exist.', 314, $this->source);
                    })()), 'trialPluginCount', []), 'name' => twig_first($this->env, craft\helpers\Template::attribute($this->env, $this->source,                 // line 315
                        (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                            throw new RuntimeError('Variable "trialInfo" does not exist.', 315, $this->source);
                        })()), 'trialPluginNames', []))]), 'html', null, true);
                // line 316
                echo '
                            ';
            }
            // line 318
            echo '                            ';
            $context['linkText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Purchase {total, plural, =1{license} other{licenses}}', 'app', ['total' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 319
                (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 319, $this->source);
                })()), 'hasCraftTrial', [])) ? (1) : (0)) + craft\helpers\Template::attribute($this->env, $this->source, (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 319, $this->source);
                })()), 'trialPluginCount', []))]);
            // line 321
            echo '                            ';
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['class' => 'go', 'href' => craft\helpers\Template::attribute($this->env, $this->source,             // line 323
                (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 323, $this->source);
                })()), 'cartUrl', []), 'target' => '_blank', 'text' =>             // line 325
(isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
    throw new RuntimeError('Variable "linkText" does not exist.', 325, $this->source);
})()), 'aria' => ['label' =>             // line 326
(isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
    throw new RuntimeError('Variable "linkText" does not exist.', 326, $this->source);
})()), ], ]);
            // line 327
            echo '
                        </span>
                    </div>
                ';
        }
        // line 331
        echo '                <div id="app-info">
                    ';
        // line 332
        $context['fullEditionName'] = $this->extensions['craft\web\twig\Extension']->translateFilter('{edition} edition', 'app', ['edition' => (isset($context['editionName']) || array_key_exists('editionName', $context) ? $context['editionName'] : (function () {
            throw new RuntimeError('Variable "editionName" does not exist.', 332, $this->source);
        })())]);
        // line 333
        echo '                    <span>
                        Craft CMS
                        <span id="edition-logo" title="';
        // line 335
        echo twig_escape_filter($this->env, (isset($context['fullEditionName']) || array_key_exists('fullEditionName', $context) ? $context['fullEditionName'] : (function () {
            throw new RuntimeError('Variable "fullEditionName" does not exist.', 335, $this->source);
        })()), 'html', null, true);
        echo '">
                            <span aria-hidden="true">';
        // line 336
        echo twig_escape_filter($this->env, (isset($context['editionName']) || array_key_exists('editionName', $context) ? $context['editionName'] : (function () {
            throw new RuntimeError('Variable "editionName" does not exist.', 336, $this->source);
        })()), 'html', null, true);
        echo '</span>
                            <span class="visually-hidden">';
        // line 337
        echo twig_escape_filter($this->env, (isset($context['fullEditionName']) || array_key_exists('fullEditionName', $context) ? $context['fullEditionName'] : (function () {
            throw new RuntimeError('Variable "fullEditionName" does not exist.', 337, $this->source);
        })()), 'html', null, true);
        echo '</span>
                        </span>
                        ';
        // line 339
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 339, $this->source);
        })()), 'app', []), 'version', []), 'html', null, true);
        echo '
                    </span>
                    ';
        // line 341
        if (((isset($context['canUpgradeEdition']) || array_key_exists('canUpgradeEdition', $context) ? $context['canUpgradeEdition'] : (function () {
            throw new RuntimeError('Variable "canUpgradeEdition" does not exist.', 341, $this->source);
        })()) && ! (isset($context['isTrial']) || array_key_exists('isTrial', $context) ? $context['isTrial'] : (function () {
            throw new RuntimeError('Variable "isTrial" does not exist.', 341, $this->source);
        })()))) {
            // line 342
            echo '                        ';
            $context['linkText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Upgrade to Craft Pro', 'app');
            // line 343
            echo '                        <span>
                            <a class="go" href="';
            // line 344
            echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url('plugin-store/upgrade-craft'), 'html', null, true);
            echo '" aria-label="';
            echo twig_escape_filter($this->env, (isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
                throw new RuntimeError('Variable "linkText" does not exist.', 344, $this->source);
            })()), 'html', null, true);
            echo '">';
            echo twig_escape_filter($this->env, (isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
                throw new RuntimeError('Variable "linkText" does not exist.', 344, $this->source);
            })()), 'html', null, true);
            echo '</a>
                        </span>
                    ';
        }
        // line 347
        echo '                </div>
            </footer>

        </div><!-- #page-container -->
    </div><!-- #global-container -->
';
        craft\helpers\Template::endProfile('block', 'body');
    }

    // line 197
    public function block_mainFormAttributes($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'mainFormAttributes');
        echo craft\helpers\Html::renderTagAttributes((isset($context['mainFormAttributes']) || array_key_exists('mainFormAttributes', $context) ? $context['mainFormAttributes'] : (function () {
            throw new RuntimeError('Variable "mainFormAttributes" does not exist.', 197, $this->source);
        })()));
        craft\helpers\Template::endProfile('block', 'mainFormAttributes');
    }

    // line 204
    public function block_header($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'header');
        // line 205
        echo '                                    <div id="page-title" class="flex';
        if ((isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
            throw new RuntimeError('Variable "toolbar" does not exist.', 205, $this->source);
        })())) {
            echo ' has-toolbar';
        }
        echo '">
                                        ';
        // line 206
        $this->displayBlock('pageTitle', $context, $blocks);
        // line 211
        echo '                                    </div>
                                    ';
        // line 212
        if ((isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
            throw new RuntimeError('Variable "toolbar" does not exist.', 212, $this->source);
        })())) {
            // line 213
            echo '                                        <div id="toolbar" class="flex">
                                            ';
            // line 214
            echo isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
                throw new RuntimeError('Variable "toolbar" does not exist.', 214, $this->source);
            })();
            echo '
                                        </div>
                                    ';
        }
        // line 217
        echo '                                    ';
        if (((isset($context['actionButton']) || array_key_exists('actionButton', $context) ? $context['actionButton'] : (function () {
            throw new RuntimeError('Variable "actionButton" does not exist.', 217, $this->source);
        })()) || (isset($context['additionalButtons']) || array_key_exists('additionalButtons', $context) ? $context['additionalButtons'] : (function () {
            throw new RuntimeError('Variable "additionalButtons" does not exist.', 217, $this->source);
        })()))) {
            // line 218
            echo '                                        <div id="action-buttons" class="flex">
                                            ';
            // line 219
            echo isset($context['additionalButtons']) || array_key_exists('additionalButtons', $context) ? $context['additionalButtons'] : (function () {
                throw new RuntimeError('Variable "additionalButtons" does not exist.', 219, $this->source);
            })();
            echo '
                                            ';
            // line 220
            echo isset($context['actionButton']) || array_key_exists('actionButton', $context) ? $context['actionButton'] : (function () {
                throw new RuntimeError('Variable "actionButton" does not exist.', 220, $this->source);
            })();
            echo '
                                        </div>
                                    ';
        }
        // line 223
        echo '                                ';
        craft\helpers\Template::endProfile('block', 'header');
    }

    // line 206
    public function block_pageTitle($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'pageTitle');
        // line 207
        echo '                                            ';
        if ((array_key_exists('title', $context) && $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
            throw new RuntimeError('Variable "title" does not exist.', 207, $this->source);
        })())))) {
            // line 208
            echo '                                                <h1 class="screen-title" title="';
            echo twig_escape_filter($this->env, (isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
                throw new RuntimeError('Variable "title" does not exist.', 208, $this->source);
            })()), 'html', null, true);
            echo '">';
            echo twig_escape_filter($this->env, (isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
                throw new RuntimeError('Variable "title" does not exist.', 208, $this->source);
            })()), 'html', null, true);
            echo '</h1>
                                            ';
        }
        // line 210
        echo '                                        ';
        craft\helpers\Template::endProfile('block', 'pageTitle');
    }

    // line 248
    public function block_main($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'main');
        // line 249
        echo '                                ';
        if (! twig_test_empty((isset($context['errorSummary']) || array_key_exists('errorSummary', $context) ? $context['errorSummary'] : (function () {
            throw new RuntimeError('Variable "errorSummary" does not exist.', 249, $this->source);
        })()))) {
            // line 250
            echo '                                    ';
            echo (array_key_exists('errorSummary', $context)) ? ((isset($context['errorSummary']) || array_key_exists('errorSummary', $context) ? $context['errorSummary'] : (function () {
                throw new RuntimeError('Variable "errorSummary" does not exist.', 250, $this->source);
            })())) : ('');
            echo '
                                ';
        }
        // line 252
        echo '                                <div id="content" class="content-pane">
                                    ';
        // line 253
        if (((isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
            throw new RuntimeError('Variable "contentNotice" does not exist.', 253, $this->source);
        })()) || (isset($context['tabs']) || array_key_exists('tabs', $context) ? $context['tabs'] : (function () {
            throw new RuntimeError('Variable "tabs" does not exist.', 253, $this->source);
        })()))) {
            // line 254
            echo '                                        <header id="content-header" class="pane-header">
                                            ';
            // line 255
            echo ((isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
                throw new RuntimeError('Variable "contentNotice" does not exist.', 255, $this->source);
            })())) ? ($this->extensions['craft\web\twig\Extension']->tagFunction('div', ['id' => 'content-notice', 'html' =>             // line 257
(isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
    throw new RuntimeError('Variable "contentNotice" does not exist.', 257, $this->source);
})()), 'role' => 'status', ])) : ('');
            // line 259
            echo '
                                            ';
            // line 260
            if ((isset($context['tabs']) || array_key_exists('tabs', $context) ? $context['tabs'] : (function () {
                throw new RuntimeError('Variable "tabs" does not exist.', 260, $this->source);
            })())) {
                // line 261
                echo '                                                ';
                $this->loadTemplate('_includes/tabs', '_layouts/cp', 261)->display(twig_array_merge($context, ['containerAttributes' => ['id' => 'tabs']]));
                // line 266
                echo '                                            ';
            }
            // line 267
            echo '                                        </header>
                                    ';
        }
        // line 269
        echo '
                                    ';
        // line 270
        $this->displayBlock('content', $context, $blocks);
        // line 273
        echo '
                                    ';
        // line 275
        echo '                                    ';
        if ((isset($context['footer']) || array_key_exists('footer', $context) ? $context['footer'] : (function () {
            throw new RuntimeError('Variable "footer" does not exist.', 275, $this->source);
        })())) {
            // line 276
            echo '                                        <div id="footer" class="flex flex-justify">
                                            ';
            // line 277
            echo isset($context['footer']) || array_key_exists('footer', $context) ? $context['footer'] : (function () {
                throw new RuntimeError('Variable "footer" does not exist.', 277, $this->source);
            })();
            echo '
                                        </div>
                                    ';
        }
        // line 280
        echo '                                </div>
                            ';
        craft\helpers\Template::endProfile('block', 'main');
    }

    // line 270
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 271
        echo '                                        ';
        echo (array_key_exists('content', $context)) ? ((isset($context['content']) || array_key_exists('content', $context) ? $context['content'] : (function () {
            throw new RuntimeError('Variable "content" does not exist.', 271, $this->source);
        })())) : ('');
        echo '
                                    ';
        craft\helpers\Template::endProfile('block', 'content');
    }

    // line 355
    public function block_actionButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 356
        echo '    ';
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 356, $this->source);
        })())) {
            // line 357
            echo '        <div class="btngroup">
            ';
            // line 358
            $this->displayBlock('submitButton', $context, $blocks);
            // line 361
            echo '            ';
            if ((($context['formActions']) ?? (false))) {
                // line 362
                echo '                <button type="button" class="btn submit menubtn" aria-label="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('More actions', 'app'), 'html', null, true);
                echo '" aria-controls="form-action-menu" data-disclosure-trigger></button>
                ';
                // line 363
                $this->loadTemplate('_layouts/components/form-action-menu', '_layouts/cp', 363)->display($context);
                // line 364
                echo '            ';
            }
            // line 365
            echo '        </div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'actionButton');
    }

    // line 358
    public function block_submitButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'submitButton');
        // line 359
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
        return [721 => 359,  716 => 358,  709 => 365,  706 => 364,  704 => 363,  699 => 362,  696 => 361,  694 => 358,  691 => 357,  688 => 356,  683 => 355,  675 => 271,  670 => 270,  664 => 280,  658 => 277,  655 => 276,  652 => 275,  649 => 273,  647 => 270,  644 => 269,  640 => 267,  637 => 266,  634 => 261,  632 => 260,  629 => 259,  627 => 257,  626 => 255,  623 => 254,  621 => 253,  618 => 252,  612 => 250,  609 => 249,  604 => 248,  599 => 210,  591 => 208,  588 => 207,  583 => 206,  578 => 223,  572 => 220,  568 => 219,  565 => 218,  562 => 217,  556 => 214,  553 => 213,  551 => 212,  548 => 211,  546 => 206,  539 => 205,  534 => 204,  525 => 197,  515 => 347,  505 => 344,  502 => 343,  499 => 342,  497 => 341,  492 => 339,  487 => 337,  483 => 336,  479 => 335,  475 => 333,  473 => 332,  470 => 331,  464 => 327,  462 => 326,  461 => 325,  460 => 323,  458 => 321,  456 => 319,  454 => 318,  450 => 316,  448 => 315,  447 => 314,  445 => 313,  439 => 311,  437 => 310,  434 => 309,  432 => 308,  431 => 307,  429 => 306,  427 => 305,  423 => 303,  421 => 302,  415 => 298,  412 => 296,  410 => 295,  406 => 293,  398 => 288,  393 => 285,  391 => 284,  387 => 282,  384 => 248,  380 => 246,  378 => 245,  375 => 244,  372 => 242,  365 => 238,  357 => 233,  353 => 231,  350 => 230,  346 => 228,  343 => 227,  338 => 224,  336 => 204,  332 => 202,  330 => 201,  327 => 200,  324 => 198,  320 => 197,  318 => 196,  313 => 194,  300 => 186,  292 => 180,  286 => 178,  284 => 177,  280 => 176,  277 => 175,  271 => 173,  269 => 172,  265 => 171,  257 => 166,  252 => 164,  248 => 163,  241 => 158,  235 => 154,  231 => 153,  227 => 152,  221 => 150,  215 => 147,  212 => 146,  209 => 145,  207 => 144,  202 => 142,  199 => 141,  197 => 140,  193 => 138,  191 => 137,  186 => 134,  183 => 129,  178 => 128,  172 => 42,  167 => 372,  165 => 371,  163 => 370,  157 => 123,  155 => 122,  153 => 120,  151 => 118,  150 => 116,  149 => 115,  148 => 114,  147 => 113,  146 => 111,  145 => 110,  144 => 109,  143 => 108,  142 => 102,  140 => 100,  138 => 98,  137 => 95,  134 => 92,  132 => 91,  129 => 89,  127 => 88,  125 => 87,  123 => 86,  121 => 83,  120 => 82,  119 => 81,  117 => 79,  115 => 78,  113 => 77,  111 => 76,  109 => 75,  107 => 74,  105 => 73,  103 => 72,  101 => 71,  99 => 70,  97 => 69,  95 => 68,  93 => 66,  91 => 65,  89 => 64,  87 => 63,  85 => 62,  83 => 60,  81 => 59,  76 => 55,  73 => 54,  69 => 52,  67 => 51,  64 => 50,  62 => 49,  57 => 48,  54 => 47,  52 => 46,  50 => 45,  42 => 42];
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
                    <button type=\"button\" id=\"announcements-btn\" class=\"btn hidden\" title=\"{{ 'What’s New'|t('app') }}\">
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
                            data-disclosure-trigger>
                            {{ userPhoto|raw }}
                        </button>
                        <div id=\"account-menu\" class=\"menu menu--disclosure\" data-align=\"right\" data-align-to=\".header-photo\">
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
                                <button type=\"button\" id=\"sidebar-toggle\" class=\"btn menubtn\" aria-controls=\"sidebar-container\" aria-expanded=\"false\">
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
                            <a class=\"go\" href=\"{{ url('plugin-store/upgrade-craft') }}\" aria-label=\"{{ linkText }}\">{{ linkText }}</a>
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
                <button type=\"button\" class=\"btn submit menubtn\" aria-label=\"{{ 'More actions'|t('app') }}\" aria-controls=\"form-action-menu\" data-disclosure-trigger></button>
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
