<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* _layouts/cp */
class __TwigTemplate_353230a75c62208fff4dbdca4f65c9cc extends Template
{
    private readonly Source $source;

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
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 42
        return '_layouts/basecp.twig';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_layouts/cp');
        // line 45
        $context['queue'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 45, $this->source);
        })()), 'app', [], 'any', false, false, false, 45), 'queue', [], 'any', false, false, false, 45);
        // line 46
        ob_start();
        // line 47
        if ($this->env->getTest('instance of')->getCallable()((isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
            throw new RuntimeError('Variable "queue" does not exist.', 47, $this->source);
        })()), 'craft\\queue\\QueueInterface')) {
            // line 48
            yield '    Craft.cp.setJobInfo(';
            yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
                throw new RuntimeError('Variable "queue" does not exist.', 48, $this->source);
            })()), 'getJobInfo', [100], 'method', false, false, false, 48));
            yield ', false);
    ';
            // line 49
            if (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
                throw new RuntimeError('Variable "queue" does not exist.', 49, $this->source);
            })()), 'getHasReservedJobs', [], 'method', false, false, false, 49)) {
                // line 50
                yield '        Craft.cp.trackJobProgress(true);
    ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 51
                (isset($context['queue']) || array_key_exists('queue', $context) ? $context['queue'] : (function () {
                    throw new RuntimeError('Variable "queue" does not exist.', 51, $this->source);
                })()), 'getHasWaitingJobs', [], 'method', false, false, false, 51)) {
                // line 52
                yield '        Craft.cp.runQueue();
    ';
            }
        } else {
            // line 55
            yield '    Craft.cp.enableQueue = false;
';
        }
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        // line 59
        $context['hasSystemIcon'] = (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 59, $this->source);
        })()) >= (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 59, $this->source);
        })())) && craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 59, $this->source);
        })()), 'rebrand', [], 'any', false, false, false, 59), 'isIconUploaded', [], 'any', false, false, false, 59));
        // line 60
        $context['fullPageForm'] = (array_key_exists('fullPageForm', $context) && (isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 60, $this->source);
        })()));
        // line 62
        $context['editionName'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 62, $this->source);
        })()), 'app', [], 'any', false, false, false, 62), 'edition', [], 'any', false, false, false, 62), 'name', [], 'any', false, false, false, 62);
        // line 63
        $context['canUpgradeEdition'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 63, $this->source);
        })()), 'app', [], 'any', false, false, false, 63), 'getCanUpgradeEdition', [], 'method', false, false, false, 63);
        // line 64
        $context['licensedEdition'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 64, $this->source);
        })()), 'app', [], 'any', false, false, false, 64), 'getLicensedEdition', [], 'method', false, false, false, 64);
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
        })()), 'cp', [], 'any', false, false, false, 66), 'trialInfo', [], 'method', false, false, false, 66);
        // line 68
        $context['contentNotice'] = Twig\Extension\CoreExtension::trim((($context['contentNotice']) ?? ((($this->unwrap()->hasBlock('contentNotice', $context, $blocks)) ? ($this->unwrap()->renderBlock('contentNotice', $context, $blocks)) : ('')))));
        // line 69
        $context['sidebar'] = Twig\Extension\CoreExtension::trim((($context['sidebar']) ?? ((($this->unwrap()->hasBlock('sidebar', $context, $blocks)) ? ($this->unwrap()->renderBlock('sidebar', $context, $blocks)) : ('')))));
        // line 70
        $context['toolbar'] = Twig\Extension\CoreExtension::trim((($context['toolbar']) ?? ((($this->unwrap()->hasBlock('toolbar', $context, $blocks)) ? ($this->unwrap()->renderBlock('toolbar', $context, $blocks)) : ('')))));
        // line 71
        $context['actionButton'] = Twig\Extension\CoreExtension::trim((($this->unwrap()->hasBlock('actionButton', $context, $blocks)) ? ($this->unwrap()->renderBlock('actionButton', $context, $blocks)) : ('')));
        // line 72
        $context['additionalButtons'] ??= null;
        // line 73
        $context['details'] = Twig\Extension\CoreExtension::trim((($context['details']) ?? ((($this->unwrap()->hasBlock('details', $context, $blocks)) ? ($this->unwrap()->renderBlock('details', $context, $blocks)) : ('')))));
        // line 74
        $context['footer'] = Twig\Extension\CoreExtension::trim((($context['footer']) ?? ((($this->unwrap()->hasBlock('footer', $context, $blocks)) ? ($this->unwrap()->renderBlock('footer', $context, $blocks)) : ('')))));
        // line 75
        $context['crumbs'] ??= null;
        // line 76
        $context['contextMenu'] = Twig\Extension\CoreExtension::trim((($context['contextMenu']) ?? ((($this->unwrap()->hasBlock('contextMenu', $context, $blocks)) ? ($this->unwrap()->renderBlock('contextMenu', $context, $blocks)) : ('')))));
        // line 77
        $context['actionMenu'] ??= '';
        // line 78
        $context['tabs'] = ((($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (($context['tabs']) ?? ([]))) > 1)) ? ((isset($context['tabs']) || array_key_exists('tabs', $context) ? $context['tabs'] : (function () {
            throw new RuntimeError('Variable "tabs" does not exist.', 78, $this->source);
        })())) : (null));
        // line 79
        $context['errorSummary'] ??= null;
        // line 81
        $context['mainContentClasses'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [((        // line 82
            (isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
                throw new RuntimeError('Variable "sidebar" does not exist.', 82, $this->source);
            })())) ? ('has-sidebar') : ('')), ((        // line 83
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
        })()), 'app', [], 'any', false, false, false, 91), 'hasModule', ['debug'], 'method', false, false, false, 91)) {
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
        })()), 'cp', [], 'any', false, false, false, 100), 'prepFormActions', [(($context['formActions']) ?? (null))], 'method', false, false, false, 100);
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
                            })()), 'getIsDeltaRegistrationActive', [], 'method', false, false, false, 113), 'delta-names' => craft\helpers\Template::attribute($this->env, $this->source,         // line 114
                                (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                                    throw new RuntimeError('Variable "view" does not exist.', 114, $this->source);
                                })()), 'getDeltaNames', [], 'method', false, false, false, 114), 'initial-delta-values' => craft\helpers\Template::attribute($this->env, $this->source,         // line 115
                                    (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                                        throw new RuntimeError('Variable "view" does not exist.', 115, $this->source);
                                    })()), 'getInitialDeltaValues', [], 'method', false, false, false, 115), 'modified-delta-names' => array_unique($this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Template::attribute($this->env, $this->source,         // line 116
                                        (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                                            throw new RuntimeError('Variable "view" does not exist.', 116, $this->source);
                                        })()), 'getModifiedDeltaNames', [], 'method', false, false, false, 116), (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 116), 'request', [], 'any', false, true, false, 116), 'getBodyParam', ['modifiedDeltaNames'], 'method', true, true, false, 116) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 116), 'request', [], 'any', false, true, false, 116), 'getBodyParam', ['modifiedDeltaNames'], 'method', false, false, false, 116) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 116), 'request', [], 'any', false, true, false, 116), 'getBodyParam', ['modifiedDeltaNames'], 'method', false, false, false, 116)) : ([]))))]], ((        // line 118
                                            $context['mainFormAttributes']) ?? ([])), true);
        // line 120
        $context['userPhoto'] = Twig\Extension\CoreExtension::include($this->env, $context, '_layouts/components/header-photo.twig');
        // line 122
        ob_start();
        // line 123
        yield "// Remove the hash so the browser doesn't scroll to it
window.LOCATION_HASH = document.location.hash ? decodeURIComponent(document.location.hash.substr(1)) : null;
history.replaceState(undefined, undefined, window.location.href.match(/^[^#]*/)[0]);
";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 1]);
        // line 412
        if ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 412, $this->source);
        })()), 'can', ['performUpdates'], 'method', false, false, false, 412) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 412, $this->source);
        })()), 'app', [], 'any', false, false, false, 412), 'updates', [], 'any', false, false, false, 412), 'getIsUpdateInfoCached', [], 'method', false, false, false, 412))) {
            // line 413
            ob_start();
            // line 414
            yield '    Craft.cp.checkForUpdates();
    ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        // line 42
        $this->parent = $this->loadTemplate('_layouts/basecp.twig', '_layouts/cp', 42);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/cp');
    }

    // line 128
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_body(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'body');
        // line 129
        yield '    ';
        yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['id' => 'global-skip-link', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Skip to content', 'app'), 'href' => '#main', 'class' => 'skip-link btn']);
        // line 134
        yield '

    <div id="global-container">
        ';
        // line 137
        yield from $this->loadTemplate('_layouts/components/global-sidebar', '_layouts/cp', 137)->unwrap()->yield($context);
        // line 138
        yield '
        <div id="page-container">
            ';
        // line 140
        yield from $this->loadTemplate('_layouts/components/alerts', '_layouts/cp', 140)->unwrap()->yield($context);
        // line 141
        yield '
            <div id="global-header" role="region" aria-label="';
        // line 142
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('My Account', 'app'), 'html', null, true);
        yield '">
                <div class="flex flex-nowrap gap-xs">
                    ';
        // line 144
        yield from $this->loadTemplate('_layouts/components/crumbs', '_layouts/cp', 144)->unwrap()->yield($context);
        // line 145
        yield '                    ';
        if ((isset($context['contextMenu']) || array_key_exists('contextMenu', $context) ? $context['contextMenu'] : (function () {
            throw new RuntimeError('Variable "contextMenu" does not exist.', 145, $this->source);
        })())) {
            // line 146
            yield '                        <div id="context-menu-container" class="context-menu-container">
                            ';
            // line 147
            yield isset($context['contextMenu']) || array_key_exists('contextMenu', $context) ? $context['contextMenu'] : (function () {
                throw new RuntimeError('Variable "contextMenu" does not exist.', 147, $this->source);
            })();
            yield '
                        </div>
                    ';
        }
        // line 150
        yield '                </div>
                <button
                    type="button"
                    id="announcements-btn"
                    class="btn hidden"
                    title="';
        // line 155
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('What’s New', 'app'), 'html', null, true);
        yield '"
                >
                    <span class="visually-hidden">';
        // line 157
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('What’s New', 'app'), 'html', null, true);
        yield '</span>
                    ';
        // line 158
        yield craft\helpers\Cp::iconSvg('gift');
        yield '
                </button>

                ';
        // line 162
        yield '                <div class="account-toggle-wrapper">
                    <button
                        id="user-info"
                        aria-controls="account-menu"
                        class="btn menu-toggle"
                        aria-label="';
        // line 167
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('My Account', 'app'), 'html', null, true);
        yield '"
                        title="';
        // line 168
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('My Account', 'app'), 'html', null, true);
        yield '"
                        data-disclosure-trigger
                    >
                        ';
        // line 171
        yield isset($context['userPhoto']) || array_key_exists('userPhoto', $context) ? $context['userPhoto'] : (function () {
            throw new RuntimeError('Variable "userPhoto" does not exist.', 171, $this->source);
        })();
        yield '
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
        // line 181
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('myaccount'), 'html', null, true);
        yield '" class="flex flex-nowrap">
                                    ';
        // line 182
        if (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 182, $this->source);
        })()), 'photoId', [], 'any', false, false, false, 182)) {
            // line 183
            yield '                                        ';
            yield isset($context['userPhoto']) || array_key_exists('userPhoto', $context) ? $context['userPhoto'] : (function () {
                throw new RuntimeError('Variable "userPhoto" does not exist.', 183, $this->source);
            })();
            yield '
                                    ';
        }
        // line 185
        yield '                                    <div class="flex-grow">
                                        <div>';
        // line 186
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 186, $this->source);
        })()), 'username', [], 'any', false, false, false, 186), 'html', null, true);
        yield '</div>
                                        ';
        // line 187
        if (! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 187, $this->source);
        })()), 'app', [], 'any', false, false, false, 187), 'config', [], 'any', false, false, false, 187), 'general', [], 'any', false, false, false, 187), 'useEmailAsUsername', [], 'any', false, false, false, 187)) {
            // line 188
            yield '                                            <div class="smalltext">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                throw new RuntimeError('Variable "currentUser" does not exist.', 188, $this->source);
            })()), 'email', [], 'any', false, false, false, 188), 'html', null, true);
            yield '</div>
                                        ';
        }
        // line 190
        yield '                                    </div>
                                </a>
                            </li>
                        </ul>
                        <hr>
                        <ul>
                            <li><a href="';
        // line 196
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('logout'), 'html', null, true);
        yield '">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Sign out', 'app'), 'html', null, true);
        yield '</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="main-container">

                <main ';
        // line 204
        yield craft\helpers\Html::renderTagAttributes((isset($context['mainAttributes']) || array_key_exists('mainAttributes', $context) ? $context['mainAttributes'] : (function () {
            throw new RuntimeError('Variable "mainAttributes" does not exist.', 204, $this->source);
        })()));
        yield '>

                    ';
        // line 206
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 206, $this->source);
        })())) {
            // line 207
            yield '<form ';
            yield from $this->unwrap()->yieldBlock('mainFormAttributes', $context, $blocks);
            yield '>';
            // line 208
            yield craft\helpers\Html::csrfInput();
        }
        // line 210
        yield '
                        ';
        // line 211
        if ((isset($context['showHeader']) || array_key_exists('showHeader', $context) ? $context['showHeader'] : (function () {
            throw new RuntimeError('Variable "showHeader" does not exist.', 211, $this->source);
        })())) {
            // line 212
            yield '                            <div id="header-container">
                                <header id="header">
                                    ';
            // line 214
            yield from $this->unwrap()->yieldBlock('header', $context, $blocks);
            // line 236
            yield '                                </header><!-- #header -->
                            </div>
                        ';
        }
        // line 239
        yield '
                        <div id="main-content" class="';
        // line 240
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(Twig\Extension\CoreExtension::join((isset($context['mainContentClasses']) || array_key_exists('mainContentClasses', $context) ? $context['mainContentClasses'] : (function () {
            throw new RuntimeError('Variable "mainContentClasses" does not exist.', 240, $this->source);
        })()), ' '), 'html', null, true);
        yield '">
                            ';
        // line 242
        yield '                            ';
        if ((isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
            throw new RuntimeError('Variable "sidebar" does not exist.', 242, $this->source);
        })())) {
            // line 243
            yield '                                <div id="sidebar-toggle-container">
                                    <button
                                        type="button"
                                        id="sidebar-toggle"
                                        class="btn menubtn chromeless"
                                        aria-controls="sidebar-container"
                                        aria-expanded="false"
                                    >
                                        ';
            // line 251
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Show sidebar', 'app'), 'html', null, true);
            yield '
                                    </button>
                                </div>
                                <div id="sidebar-container">
                                    <div id="sidebar" class="sidebar">
                                        ';
            // line 256
            yield isset($context['sidebar']) || array_key_exists('sidebar', $context) ? $context['sidebar'] : (function () {
                throw new RuntimeError('Variable "sidebar" does not exist.', 256, $this->source);
            })();
            yield '
                                    </div>
                                </div>
                            ';
        }
        // line 260
        yield '
                            ';
        // line 262
        yield '                            <div id="content-container">
                                <div class="content-grid">

                                    ';
        // line 265
        yield from $this->unwrap()->yieldBlock('main', $context, $blocks);
        // line 325
        yield '                                </div>
                            </div><!-- #content-container -->

                            ';
        // line 328
        if (! Twig\Extension\CoreExtension::testEmpty((isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
            throw new RuntimeError('Variable "details" does not exist.', 328, $this->source);
        })()))) {
            // line 329
            yield '                                <div id="details-container">
                                    <div id="details">
                                        <div class="details">
                                            ';
            // line 332
            yield isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
                throw new RuntimeError('Variable "details" does not exist.', 332, $this->source);
            })();
            yield '
                                        </div>
                                    </div>
                                </div>
                            ';
        }
        // line 337
        yield '                        </div><!-- #main-content -->

                        ';
        // line 339
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 339, $this->source);
        })())) {
            // line 340
            yield '</form><!-- #main-form -->';
        }
        // line 342
        yield '                </main><!-- #main -->
            </div><!-- #main-container -->

            <footer id="global-footer">
                ';
        // line 346
        if ((isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
            throw new RuntimeError('Variable "trialInfo" does not exist.', 346, $this->source);
        })())) {
            // line 347
            yield '                    <div id="trial-info" class="readable">
                        <span>
                            ';
            // line 349
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                throw new RuntimeError('Variable "trialInfo" does not exist.', 349, $this->source);
            })()), 'message', [], 'any', false, false, false, 349), 'html', null, true);
            yield '
                            ';
            // line 350
            $context['linkText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Buy now', 'app');
            // line 351
            yield '                            ';
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['class' => 'go', 'href' => craft\helpers\Template::attribute($this->env, $this->source,             // line 353
                (isset($context['trialInfo']) || array_key_exists('trialInfo', $context) ? $context['trialInfo'] : (function () {
                    throw new RuntimeError('Variable "trialInfo" does not exist.', 353, $this->source);
                })()), 'cartUrl', [], 'any', false, false, false, 353), 'target' => '_blank', 'text' =>             // line 355
(isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
    throw new RuntimeError('Variable "linkText" does not exist.', 355, $this->source);
})()), 'aria' => ['label' =>             // line 356
(isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
    throw new RuntimeError('Variable "linkText" does not exist.', 356, $this->source);
})())]]);
            // line 357
            yield '
                        </span>
                    </div>
                ';
        }
        // line 361
        yield '                <div id="app-info">
                    ';
        // line 362
        $context['fullEditionName'] = $this->extensions['craft\web\twig\Extension']->translateFilter('{edition} edition', 'app', ['edition' => (isset($context['editionName']) || array_key_exists('editionName', $context) ? $context['editionName'] : (function () {
            throw new RuntimeError('Variable "editionName" does not exist.', 362, $this->source);
        })())]);
        // line 363
        yield '                    <span>
                        <span lang="en">
                            Craft CMS
                            <span id="edition-logo" title="';
        // line 366
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['fullEditionName']) || array_key_exists('fullEditionName', $context) ? $context['fullEditionName'] : (function () {
            throw new RuntimeError('Variable "fullEditionName" does not exist.', 366, $this->source);
        })()), 'html', null, true);
        yield '">
                                <span aria-hidden="true">';
        // line 367
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['editionName']) || array_key_exists('editionName', $context) ? $context['editionName'] : (function () {
            throw new RuntimeError('Variable "editionName" does not exist.', 367, $this->source);
        })()), 'html', null, true);
        yield '</span>
                                <span class="visually-hidden">';
        // line 368
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['fullEditionName']) || array_key_exists('fullEditionName', $context) ? $context['fullEditionName'] : (function () {
            throw new RuntimeError('Variable "fullEditionName" does not exist.', 368, $this->source);
        })()), 'html', null, true);
        yield '</span>
                            </span>
                        </span>
                        ';
        // line 371
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 371, $this->source);
        })()), 'app', [], 'any', false, false, false, 371), 'version', [], 'any', false, false, false, 371), 'html', null, true);
        yield '
                    </span>
                    ';
        // line 373
        if (((isset($context['canUpgradeEdition']) || array_key_exists('canUpgradeEdition', $context) ? $context['canUpgradeEdition'] : (function () {
            throw new RuntimeError('Variable "canUpgradeEdition" does not exist.', 373, $this->source);
        })()) && ! (isset($context['isTrial']) || array_key_exists('isTrial', $context) ? $context['isTrial'] : (function () {
            throw new RuntimeError('Variable "isTrial" does not exist.', 373, $this->source);
        })()))) {
            // line 374
            yield '                        ';
            $context['linkText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Upgrade to Craft Pro', 'app');
            // line 375
            yield '                        <span>
                            <a
                                class="go"
                                href="';
            // line 378
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('plugin-store/upgrade-craft'), 'html', null, true);
            yield '"
                                aria-label="';
            // line 379
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
                throw new RuntimeError('Variable "linkText" does not exist.', 379, $this->source);
            })()), 'html', null, true);
            yield '"
                            >';
            // line 380
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['linkText']) || array_key_exists('linkText', $context) ? $context['linkText'] : (function () {
                throw new RuntimeError('Variable "linkText" does not exist.', 380, $this->source);
            })()), 'html', null, true);
            yield '</a>
                        </span>
                    ';
        }
        // line 383
        yield '                </div>
            </footer>

        </div><!-- #page-container -->
    </div><!-- #global-container -->
';
        craft\helpers\Template::endProfile('block', 'body');
        yield from [];
    }

    // line 207
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_mainFormAttributes(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'mainFormAttributes');
        yield craft\helpers\Html::renderTagAttributes((isset($context['mainFormAttributes']) || array_key_exists('mainFormAttributes', $context) ? $context['mainFormAttributes'] : (function () {
            throw new RuntimeError('Variable "mainFormAttributes" does not exist.', 207, $this->source);
        })()));
        craft\helpers\Template::endProfile('block', 'mainFormAttributes');
        yield from [];
    }

    // line 214
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_header(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'header');
        // line 215
        yield '                                        <div id="page-title" class="flex';
        if ((isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
            throw new RuntimeError('Variable "toolbar" does not exist.', 215, $this->source);
        })())) {
            yield ' has-toolbar';
        }
        yield '">
                                            ';
        // line 216
        yield from $this->unwrap()->yieldBlock('pageTitle', $context, $blocks);
        // line 221
        yield '                                        </div>
                                        ';
        // line 222
        if ((isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
            throw new RuntimeError('Variable "toolbar" does not exist.', 222, $this->source);
        })())) {
            // line 223
            yield '                                            <div id="toolbar" class="flex">
                                                ';
            // line 224
            yield isset($context['toolbar']) || array_key_exists('toolbar', $context) ? $context['toolbar'] : (function () {
                throw new RuntimeError('Variable "toolbar" does not exist.', 224, $this->source);
            })();
            yield '
                                            </div>
                                        ';
        }
        // line 227
        yield '                                        ';
        if (((((isset($context['actionButton']) || array_key_exists('actionButton', $context) ? $context['actionButton'] : (function () {
            throw new RuntimeError('Variable "actionButton" does not exist.', 227, $this->source);
        })()) || (isset($context['additionalButtons']) || array_key_exists('additionalButtons', $context) ? $context['additionalButtons'] : (function () {
            throw new RuntimeError('Variable "additionalButtons" does not exist.', 227, $this->source);
        })())) || (isset($context['actionMenu']) || array_key_exists('actionMenu', $context) ? $context['actionMenu'] : (function () {
            throw new RuntimeError('Variable "actionMenu" does not exist.', 227, $this->source);
        })())) || (isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
            throw new RuntimeError('Variable "details" does not exist.', 227, $this->source);
        })()))) {
            // line 228
            yield '                                            <div id="action-buttons" class="flex">
                                                ';
            // line 229
            yield isset($context['additionalButtons']) || array_key_exists('additionalButtons', $context) ? $context['additionalButtons'] : (function () {
                throw new RuntimeError('Variable "additionalButtons" does not exist.', 229, $this->source);
            })();
            yield '
                                                ';
            // line 230
            yield isset($context['actionButton']) || array_key_exists('actionButton', $context) ? $context['actionButton'] : (function () {
                throw new RuntimeError('Variable "actionButton" does not exist.', 230, $this->source);
            })();
            yield '
                                                ';
            // line 231
            yield isset($context['actionMenu']) || array_key_exists('actionMenu', $context) ? $context['actionMenu'] : (function () {
                throw new RuntimeError('Variable "actionMenu" does not exist.', 231, $this->source);
            })();
            yield '

                                            </div>
                                        ';
        }
        // line 235
        yield '                                    ';
        craft\helpers\Template::endProfile('block', 'header');
        yield from [];
    }

    // line 216
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_pageTitle(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'pageTitle');
        // line 217
        yield '                                                ';
        if ((array_key_exists('title', $context) && $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
            throw new RuntimeError('Variable "title" does not exist.', 217, $this->source);
        })())))) {
            // line 218
            yield '                                                    <h1 class="screen-title" title="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
                throw new RuntimeError('Variable "title" does not exist.', 218, $this->source);
            })()), 'html', null, true);
            yield '">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
                throw new RuntimeError('Variable "title" does not exist.', 218, $this->source);
            })()), 'html', null, true);
            yield '</h1>
                                                ';
        }
        // line 220
        yield '                                            ';
        craft\helpers\Template::endProfile('block', 'pageTitle');
        yield from [];
    }

    // line 265
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_main(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'main');
        // line 266
        yield '                                        <div class="content-grid__main">
                                            ';
        // line 267
        if (! Twig\Extension\CoreExtension::testEmpty((isset($context['errorSummary']) || array_key_exists('errorSummary', $context) ? $context['errorSummary'] : (function () {
            throw new RuntimeError('Variable "errorSummary" does not exist.', 267, $this->source);
        })()))) {
            // line 268
            yield '                                                ';
            yield (array_key_exists('errorSummary', $context)) ? ((isset($context['errorSummary']) || array_key_exists('errorSummary', $context) ? $context['errorSummary'] : (function () {
                throw new RuntimeError('Variable "errorSummary" does not exist.', 268, $this->source);
            })())) : ('');
            yield '
                                            ';
        }
        // line 270
        yield '
                                            <div id="content" class="content-pane">
                                                ';
        // line 272
        if (((isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
            throw new RuntimeError('Variable "contentNotice" does not exist.', 272, $this->source);
        })()) || (isset($context['tabs']) || array_key_exists('tabs', $context) ? $context['tabs'] : (function () {
            throw new RuntimeError('Variable "tabs" does not exist.', 272, $this->source);
        })()))) {
            // line 273
            yield '                                                    <header id="content-header" class="pane-header">
                                                        ';
            // line 274
            yield ((isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
                throw new RuntimeError('Variable "contentNotice" does not exist.', 274, $this->source);
            })())) ? ($this->extensions['craft\web\twig\Extension']->tagFunction('div', ['id' => 'content-notice', 'html' =>             // line 276
(isset($context['contentNotice']) || array_key_exists('contentNotice', $context) ? $context['contentNotice'] : (function () {
    throw new RuntimeError('Variable "contentNotice" does not exist.', 276, $this->source);
})()), 'role' => 'status'])) : ('');
            // line 278
            yield '
                                                        ';
            // line 279
            if ((isset($context['tabs']) || array_key_exists('tabs', $context) ? $context['tabs'] : (function () {
                throw new RuntimeError('Variable "tabs" does not exist.', 279, $this->source);
            })())) {
                // line 280
                yield '                                                            ';
                yield from $this->loadTemplate('_includes/tabs', '_layouts/cp', 280)->unwrap()->yield(CoreExtension::merge($context, ['containerAttributes' => ['id' => 'tabs']]));
                // line 285
                yield '                                                        ';
            }
            // line 286
            yield '                                                    </header>
                                                ';
        }
        // line 288
        yield '
                                                ';
        // line 289
        yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
        // line 292
        yield '
                                                ';
        // line 294
        yield '                                                ';
        if ((isset($context['footer']) || array_key_exists('footer', $context) ? $context['footer'] : (function () {
            throw new RuntimeError('Variable "footer" does not exist.', 294, $this->source);
        })())) {
            // line 295
            yield '                                                    <div id="footer" class="flex flex-justify">
                                                        ';
            // line 296
            yield isset($context['footer']) || array_key_exists('footer', $context) ? $context['footer'] : (function () {
                throw new RuntimeError('Variable "footer" does not exist.', 296, $this->source);
            })();
            yield '
                                                    </div>
                                                ';
        }
        // line 299
        yield '                                            </div>
                                        </div>

                                        ';
        // line 302
        if (! Twig\Extension\CoreExtension::testEmpty((isset($context['details']) || array_key_exists('details', $context) ? $context['details'] : (function () {
            throw new RuntimeError('Variable "details" does not exist.', 302, $this->source);
        })()))) {
            // line 303
            yield '                                            <div class="content-grid__toggle">
                                                ';
            // line 304
            yield from $this->loadTemplate('_layouts/cp', '_layouts/cp', 304, '762831206')->unwrap()->yield(CoreExtension::toArray(['id' => 'details-toggle', 'controls' => 'details-container']));
            // line 322
            yield '                                            </div>
                                        ';
        }
        // line 324
        yield '                                    ';
        craft\helpers\Template::endProfile('block', 'main');
        yield from [];
    }

    // line 289
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 290
        yield '                                                    ';
        yield (array_key_exists('content', $context)) ? ((isset($context['content']) || array_key_exists('content', $context) ? $context['content'] : (function () {
            throw new RuntimeError('Variable "content" does not exist.', 290, $this->source);
        })())) : ('');
        yield '
                                                ';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    // line 391
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_actionButton(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 392
        yield '    ';
        if ((isset($context['fullPageForm']) || array_key_exists('fullPageForm', $context) ? $context['fullPageForm'] : (function () {
            throw new RuntimeError('Variable "fullPageForm" does not exist.', 392, $this->source);
        })())) {
            // line 393
            yield '        <div class="btngroup">
            ';
            // line 394
            yield from $this->unwrap()->yieldBlock('submitButton', $context, $blocks);
            // line 397
            yield '            ';
            if ((($context['formActions']) ?? (false))) {
                // line 398
                yield '                <button
                    type="button"
                    class="btn submit menubtn"
                    aria-label="';
                // line 401
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('More actions', 'app'), 'html', null, true);
                yield '"
                    aria-controls="form-action-menu"
                    data-disclosure-trigger
                ></button>
                ';
                // line 405
                yield from $this->loadTemplate('_layouts/components/form-action-menu', '_layouts/cp', 405)->unwrap()->yield($context);
                // line 406
                yield '            ';
            }
            // line 407
            yield '        </div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'actionButton');
        yield from [];
    }

    // line 394
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_submitButton(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'submitButton');
        // line 395
        yield '                <button type="submit" class="btn submit">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((($context['submitButtonLabel']) ?? ($this->extensions['craft\web\twig\Extension']->translateFilter('Save', 'app'))), 'html', null, true);
        yield '</button>
            ';
        craft\helpers\Template::endProfile('block', 'submitButton');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/cp';
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
        return [777 => 395,  769 => 394,  761 => 407,  758 => 406,  756 => 405,  749 => 401,  744 => 398,  741 => 397,  739 => 394,  736 => 393,  733 => 392,  725 => 391,  716 => 290,  708 => 289,  702 => 324,  698 => 322,  696 => 304,  693 => 303,  691 => 302,  686 => 299,  680 => 296,  677 => 295,  674 => 294,  671 => 292,  669 => 289,  666 => 288,  662 => 286,  659 => 285,  656 => 280,  654 => 279,  651 => 278,  649 => 276,  648 => 274,  645 => 273,  643 => 272,  639 => 270,  633 => 268,  631 => 267,  628 => 266,  620 => 265,  614 => 220,  606 => 218,  603 => 217,  595 => 216,  589 => 235,  582 => 231,  578 => 230,  574 => 229,  571 => 228,  568 => 227,  562 => 224,  559 => 223,  557 => 222,  554 => 221,  552 => 216,  545 => 215,  537 => 214,  524 => 207,  513 => 383,  507 => 380,  503 => 379,  499 => 378,  494 => 375,  491 => 374,  489 => 373,  484 => 371,  478 => 368,  474 => 367,  470 => 366,  465 => 363,  463 => 362,  460 => 361,  454 => 357,  452 => 356,  451 => 355,  450 => 353,  448 => 351,  446 => 350,  442 => 349,  438 => 347,  436 => 346,  430 => 342,  427 => 340,  425 => 339,  421 => 337,  413 => 332,  408 => 329,  406 => 328,  401 => 325,  399 => 265,  394 => 262,  391 => 260,  384 => 256,  376 => 251,  366 => 243,  363 => 242,  359 => 240,  356 => 239,  351 => 236,  349 => 214,  345 => 212,  343 => 211,  340 => 210,  337 => 208,  333 => 207,  331 => 206,  326 => 204,  313 => 196,  305 => 190,  299 => 188,  297 => 187,  293 => 186,  290 => 185,  284 => 183,  282 => 182,  278 => 181,  265 => 171,  259 => 168,  255 => 167,  248 => 162,  242 => 158,  238 => 157,  233 => 155,  226 => 150,  220 => 147,  217 => 146,  214 => 145,  212 => 144,  207 => 142,  204 => 141,  202 => 140,  198 => 138,  196 => 137,  191 => 134,  188 => 129,  180 => 128,  174 => 42,  169 => 414,  167 => 413,  165 => 412,  159 => 123,  157 => 122,  155 => 120,  153 => 118,  152 => 116,  151 => 115,  150 => 114,  149 => 113,  148 => 111,  147 => 110,  146 => 109,  145 => 108,  144 => 102,  142 => 100,  140 => 98,  139 => 95,  136 => 92,  134 => 91,  131 => 89,  129 => 88,  127 => 87,  125 => 86,  123 => 83,  122 => 82,  121 => 81,  119 => 79,  117 => 78,  115 => 77,  113 => 76,  111 => 75,  109 => 74,  107 => 73,  105 => 72,  103 => 71,  101 => 70,  99 => 69,  97 => 68,  95 => 66,  93 => 65,  91 => 64,  89 => 63,  87 => 62,  85 => 60,  83 => 59,  78 => 55,  73 => 52,  71 => 51,  68 => 50,  66 => 49,  61 => 48,  59 => 47,  57 => 46,  55 => 45,  47 => 42];
    }

    public function getSourceContext(): Source
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

{% set hasSystemIcon = CraftEdition >= CraftPro and craft.rebrand.isIconUploaded %}
{% set fullPageForm = (fullPageForm is defined and fullPageForm) %}

{% set editionName = craft.app.edition.name %}
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
        'modified-delta-names': view.getModifiedDeltaNames()|merge(craft.app.request.getBodyParam('modifiedDeltaNames') ?? [])|unique,
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
                </div>
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
                                        {% if actionButton or additionalButtons or actionMenu or details %}
                                            <div id=\"action-buttons\" class=\"flex\">
                                                {{ additionalButtons|raw }}
                                                {{ actionButton|raw }}
                                                {{ actionMenu|raw }}

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
                                        class=\"btn menubtn chromeless\"
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
                                <div class=\"content-grid\">

                                    {% block main %}
                                        <div class=\"content-grid__main\">
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
                                        </div>

                                        {% if details is not empty %}
                                            <div class=\"content-grid__toggle\">
                                                {% embed '_includes/disclosure-toggle' with {
                                                    id: 'details-toggle',
                                                    controls: 'details-container',
                                                } only %}
                                                    {% block content %}
                                                        <span class=\"details-toggle__inner\">
                                                            <span
                                                                aria-hidden=\"true\"
                                                                class=\"cp-icon toggle-icon toggle-icon--close\"
                                                            >{{ iconSvg('angle-right') }}</span>
                                                            <span
                                                                aria-hidden=\"true\"
                                                                class=\"cp-icon toggle-icon toggle-icon--open\"
                                                            >{{ iconSvg('angle-left') }}</span>
                                                            <span class=\"visually-hidden\">{{ 'Toggle details sidebar'|t('app') }}</span>
                                                        </span>
                                                    {% endblock %}
                                                {% endembed %}
                                            </div>
                                        {% endif %}
                                    {% endblock %}
                                </div>
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
                            {{ trialInfo.message }}
                            {% set linkText = 'Buy now'|t('app') %}
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
                        <span lang=\"en\">
                            Craft CMS
                            <span id=\"edition-logo\" title=\"{{ fullEditionName }}\">
                                <span aria-hidden=\"true\">{{ editionName }}</span>
                                <span class=\"visually-hidden\">{{ fullEditionName }}</span>
                            </span>
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
", '_layouts/cp', '/tmp/packages/craft5/src/templates/_layouts/cp.twig');
    }
}

/* _layouts/cp */
class __TwigTemplate_353230a75c62208fff4dbdca4f65c9cc___762831206 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 304
        return '_includes/disclosure-toggle';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_layouts/cp');
        $this->parent = $this->loadTemplate('_includes/disclosure-toggle', '_layouts/cp', 304);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/cp');
    }

    // line 308
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 309
        yield '                                                        <span class="details-toggle__inner">
                                                            <span
                                                                aria-hidden="true"
                                                                class="cp-icon toggle-icon toggle-icon--close"
                                                            >';
        // line 313
        yield craft\helpers\Cp::iconSvg('angle-right');
        yield '</span>
                                                            <span
                                                                aria-hidden="true"
                                                                class="cp-icon toggle-icon toggle-icon--open"
                                                            >';
        // line 317
        yield craft\helpers\Cp::iconSvg('angle-left');
        yield '</span>
                                                            <span class="visually-hidden">';
        // line 318
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Toggle details sidebar', 'app'), 'html', null, true);
        yield '</span>
                                                        </span>
                                                    ';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/cp';
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
        return [1293 => 318,  1289 => 317,  1282 => 313,  1276 => 309,  1268 => 308,  1255 => 304,  777 => 395,  769 => 394,  761 => 407,  758 => 406,  756 => 405,  749 => 401,  744 => 398,  741 => 397,  739 => 394,  736 => 393,  733 => 392,  725 => 391,  716 => 290,  708 => 289,  702 => 324,  698 => 322,  696 => 304,  693 => 303,  691 => 302,  686 => 299,  680 => 296,  677 => 295,  674 => 294,  671 => 292,  669 => 289,  666 => 288,  662 => 286,  659 => 285,  656 => 280,  654 => 279,  651 => 278,  649 => 276,  648 => 274,  645 => 273,  643 => 272,  639 => 270,  633 => 268,  631 => 267,  628 => 266,  620 => 265,  614 => 220,  606 => 218,  603 => 217,  595 => 216,  589 => 235,  582 => 231,  578 => 230,  574 => 229,  571 => 228,  568 => 227,  562 => 224,  559 => 223,  557 => 222,  554 => 221,  552 => 216,  545 => 215,  537 => 214,  524 => 207,  513 => 383,  507 => 380,  503 => 379,  499 => 378,  494 => 375,  491 => 374,  489 => 373,  484 => 371,  478 => 368,  474 => 367,  470 => 366,  465 => 363,  463 => 362,  460 => 361,  454 => 357,  452 => 356,  451 => 355,  450 => 353,  448 => 351,  446 => 350,  442 => 349,  438 => 347,  436 => 346,  430 => 342,  427 => 340,  425 => 339,  421 => 337,  413 => 332,  408 => 329,  406 => 328,  401 => 325,  399 => 265,  394 => 262,  391 => 260,  384 => 256,  376 => 251,  366 => 243,  363 => 242,  359 => 240,  356 => 239,  351 => 236,  349 => 214,  345 => 212,  343 => 211,  340 => 210,  337 => 208,  333 => 207,  331 => 206,  326 => 204,  313 => 196,  305 => 190,  299 => 188,  297 => 187,  293 => 186,  290 => 185,  284 => 183,  282 => 182,  278 => 181,  265 => 171,  259 => 168,  255 => 167,  248 => 162,  242 => 158,  238 => 157,  233 => 155,  226 => 150,  220 => 147,  217 => 146,  214 => 145,  212 => 144,  207 => 142,  204 => 141,  202 => 140,  198 => 138,  196 => 137,  191 => 134,  188 => 129,  180 => 128,  174 => 42,  169 => 414,  167 => 413,  165 => 412,  159 => 123,  157 => 122,  155 => 120,  153 => 118,  152 => 116,  151 => 115,  150 => 114,  149 => 113,  148 => 111,  147 => 110,  146 => 109,  145 => 108,  144 => 102,  142 => 100,  140 => 98,  139 => 95,  136 => 92,  134 => 91,  131 => 89,  129 => 88,  127 => 87,  125 => 86,  123 => 83,  122 => 82,  121 => 81,  119 => 79,  117 => 78,  115 => 77,  113 => 76,  111 => 75,  109 => 74,  107 => 73,  105 => 72,  103 => 71,  101 => 70,  99 => 69,  97 => 68,  95 => 66,  93 => 65,  91 => 64,  89 => 63,  87 => 62,  85 => 60,  83 => 59,  78 => 55,  73 => 52,  71 => 51,  68 => 50,  66 => 49,  61 => 48,  59 => 47,  57 => 46,  55 => 45,  47 => 42];
    }

    public function getSourceContext(): Source
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

{% set hasSystemIcon = CraftEdition >= CraftPro and craft.rebrand.isIconUploaded %}
{% set fullPageForm = (fullPageForm is defined and fullPageForm) %}

{% set editionName = craft.app.edition.name %}
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
        'modified-delta-names': view.getModifiedDeltaNames()|merge(craft.app.request.getBodyParam('modifiedDeltaNames') ?? [])|unique,
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
                </div>
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
                                        {% if actionButton or additionalButtons or actionMenu or details %}
                                            <div id=\"action-buttons\" class=\"flex\">
                                                {{ additionalButtons|raw }}
                                                {{ actionButton|raw }}
                                                {{ actionMenu|raw }}

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
                                        class=\"btn menubtn chromeless\"
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
                                <div class=\"content-grid\">

                                    {% block main %}
                                        <div class=\"content-grid__main\">
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
                                        </div>

                                        {% if details is not empty %}
                                            <div class=\"content-grid__toggle\">
                                                {% embed '_includes/disclosure-toggle' with {
                                                    id: 'details-toggle',
                                                    controls: 'details-container',
                                                } only %}
                                                    {% block content %}
                                                        <span class=\"details-toggle__inner\">
                                                            <span
                                                                aria-hidden=\"true\"
                                                                class=\"cp-icon toggle-icon toggle-icon--close\"
                                                            >{{ iconSvg('angle-right') }}</span>
                                                            <span
                                                                aria-hidden=\"true\"
                                                                class=\"cp-icon toggle-icon toggle-icon--open\"
                                                            >{{ iconSvg('angle-left') }}</span>
                                                            <span class=\"visually-hidden\">{{ 'Toggle details sidebar'|t('app') }}</span>
                                                        </span>
                                                    {% endblock %}
                                                {% endembed %}
                                            </div>
                                        {% endif %}
                                    {% endblock %}
                                </div>
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
                            {{ trialInfo.message }}
                            {% set linkText = 'Buy now'|t('app') %}
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
                        <span lang=\"en\">
                            Craft CMS
                            <span id=\"edition-logo\" title=\"{{ fullEditionName }}\">
                                <span aria-hidden=\"true\">{{ editionName }}</span>
                                <span class=\"visually-hidden\">{{ fullEditionName }}</span>
                            </span>
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
", '_layouts/cp', '/tmp/packages/craft5/src/templates/_layouts/cp.twig');
    }
}
