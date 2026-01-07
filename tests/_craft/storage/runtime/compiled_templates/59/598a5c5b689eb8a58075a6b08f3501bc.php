<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _components/widgets/CraftSupport/body.twig */
class __TwigTemplate_4acec2556cdb3cac998c47d3683410e5 extends Template
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
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/widgets/CraftSupport/body.twig');
        // line 1
        $macros['links'] = $this->macros['links'] = $this->loadTemplate('_includes/links', '_components/widgets/CraftSupport/body.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 11
        echo '
';
        // line 149
        echo '
';
        // line 150
        $macros['__internal_parse_1'] = $this->macros['__internal_parse_1'] = $this;
        // line 151
        echo '

<div class="cs-screen cs-screen-home">
    <button type="button" class="cs-opt" data-screen="help" aria-controls="cs-screen-help" aria-expanded="false">
        <div class="cs-opt-icon">';
        // line 155
        echo craft\helpers\Cp::iconSvg('life-ring');
        echo '</div>
        <h2>';
        // line 156
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Get help', 'app'), 'html', null, true);
        echo '</h2>
        <p>';
        // line 157
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('How-to’s and other questions', 'app'), 'html', null, true);
        echo '</p>
    </button>
    <button type="button" class="cs-opt" data-screen="feedback" aria-controls="cs-screen-feedback" aria-expanded="false">
        <div class="cs-opt-icon">';
        // line 160
        echo craft\helpers\Cp::iconSvg('bullhorn');
        echo '</div>
        <h2>';
        // line 161
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Give feedback', 'app'), 'html', null, true);
        echo '</h2>
        <p>';
        // line 162
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Bug reports and feature requests', 'app'), 'html', null, true);
        echo '</p>
    </button>
</div>

';
        // line 166
        echo twig_call_macro($macros['__internal_parse_1'], 'macro_screen', [        // line 167
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 167, $this->source);
            })()),         // line 168
            (isset($context['showBackupOption']) || array_key_exists('showBackupOption', $context) ? $context['showBackupOption'] : (function () {
                throw new RuntimeError('Variable "showBackupOption" does not exist.', 168, $this->source);
            })()),         // line 169
            (isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
                throw new RuntimeError('Variable "bundleUrl" does not exist.', 169, $this->source);
            })()), 'help', $this->extensions['craft\web\twig\Extension']->translateFilter('Briefly describe your question.', 'app'), craft\helpers\Cp::iconSvg('craft-stack-exchange'), $this->extensions['craft\web\twig\Extension']->translateFilter('Similar questions on Stack Exchange', 'app'), 'https://craftcms.stackexchange.com/questions/ask', $this->extensions['craft\web\twig\Extension']->translateFilter('Ask on Stack Exchange', 'app'), ], 166, $context, $this->getSourceContext());
        // line 176
        echo '

';
        // line 178
        echo twig_call_macro($macros['__internal_parse_1'], 'macro_screen', [        // line 179
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 179, $this->source);
            })()),         // line 180
            (isset($context['showBackupOption']) || array_key_exists('showBackupOption', $context) ? $context['showBackupOption'] : (function () {
                throw new RuntimeError('Variable "showBackupOption" does not exist.', 180, $this->source);
            })()),         // line 181
            (isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
                throw new RuntimeError('Variable "bundleUrl" does not exist.', 181, $this->source);
            })()), 'feedback', $this->extensions['craft\web\twig\Extension']->translateFilter('Briefly describe your issue or idea.', 'app'), craft\helpers\Cp::iconSvg('github'), $this->extensions['craft\web\twig\Extension']->translateFilter('Similar issues on GitHub', 'app'), 'https://github.com/craftcms/cms/issues/new', $this->extensions['craft\web\twig\Extension']->translateFilter('Post on GitHub', 'app'), ], 178, $context, $this->getSourceContext());
        // line 188
        echo '
';
        craft\helpers\Template::endProfile('template', '_components/widgets/CraftSupport/body.twig');
    }

    // line 3
    public function macro_resourceLink($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'resourceLink');
            // line 4
            echo '    <a href="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 4, $this->source);
            })()), 'link', []), 'html', null, true);
            echo '" target="_blank" rel="noopener">
        <h4 class="cs-resource-heading">
            <img class="cs-logo-image" src="';
            // line 6
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 6, $this->source);
            })()), 'bundleUrl', []), 'html', null, true);
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 6, $this->source);
            })()), 'iconPath', []), 'html', null, true);
            echo '" alt="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 6, $this->source);
            })()), 'title', []), 'html', null, true);
            echo '">
        </h4>
        <p>';
            // line 8
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 8, $this->source);
            })()), 'description', []), 'html', null, true);
            echo ' ';
            echo twig_call_macro($macros['links'], 'macro_externalLinkIcon', [], 8, $context, $this->getSourceContext());
            echo '</p>
    </a>
';
            craft\helpers\Template::endProfile('macro', 'resourceLink');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 12
    public function macro_screen($__widget__ = null, $__showBackupOption__ = null, $__bundleUrl__ = null, $__screen__ = null, $__placeholder__ = null, $__resultsIcon__ = null, $__resultsHeading__ = null, $__formAction__ = null, $__submitText__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'widget' => $__widget__,
            'showBackupOption' => $__showBackupOption__,
            'bundleUrl' => $__bundleUrl__,
            'screen' => $__screen__,
            'placeholder' => $__placeholder__,
            'resultsIcon' => $__resultsIcon__,
            'resultsHeading' => $__resultsHeading__,
            'formAction' => $__formAction__,
            'submitText' => $__submitText__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'screen');
            // line 13
            echo '    ';
            $macros['forms'] = $this->loadTemplate('_includes/forms', '_components/widgets/CraftSupport/body.twig', 13)->unwrap();
            // line 14
            echo '    ';
            $context['idPrefix'] = (('cs-'.(isset($context['screen']) || array_key_exists('screen', $context) ? $context['screen'] : (function () {
                throw new RuntimeError('Variable "screen" does not exist.', 14, $this->source);
            })())).twig_random($this->env));
            // line 15
            echo '
    <div id="cs-screen-';
            // line 16
            echo twig_escape_filter($this->env, (isset($context['screen']) || array_key_exists('screen', $context) ? $context['screen'] : (function () {
                throw new RuntimeError('Variable "screen" does not exist.', 16, $this->source);
            })()), 'html', null, true);
            echo '" class="cs-screen cs-screen-2 cs-screen-';
            echo twig_escape_filter($this->env, (isset($context['screen']) || array_key_exists('screen', $context) ? $context['screen'] : (function () {
                throw new RuntimeError('Variable "screen" does not exist.', 16, $this->source);
            })()), 'html', null, true);
            echo '" action="';
            echo twig_escape_filter($this->env, (isset($context['formAction']) || array_key_exists('formAction', $context) ? $context['formAction'] : (function () {
                throw new RuntimeError('Variable "formAction" does not exist.', 16, $this->source);
            })()), 'html', null, true);
            echo '" method="get" target="_blank" rel="noopener">
        ';
            // line 17
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('h2', ['text' =>             // line 18
(isset($context['submitText']) || array_key_exists('submitText', $context) ? $context['submitText'] : (function () {
    throw new RuntimeError('Variable "submitText" does not exist.', 18, $this->source);
})()), 'class' => 'cs-heading', ]);
            // line 20
            echo '
        ';
            // line 21
            echo twig_call_macro($macros['forms'], 'macro_textareaField', [['first' => true, 'class' => 'cs-body-text', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter(            // line 24
                (isset($context['placeholder']) || array_key_exists('placeholder', $context) ? $context['placeholder'] : (function () {
                    throw new RuntimeError('Variable "placeholder" does not exist.', 24, $this->source);
                })()), 'app'), 'rows' => 5]], 21, $context, $this->getSourceContext());
            // line 26
            echo '
        <div class="cs-search-results-container hidden">
            <div class="cs-search-icon">';
            // line 28
            echo $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['resultsIcon']) || array_key_exists('resultsIcon', $context) ? $context['resultsIcon'] : (function () {
                throw new RuntimeError('Variable "resultsIcon" does not exist.', 28, $this->source);
            })()), false);
            echo '</div>
            <h2>';
            // line 29
            echo twig_escape_filter($this->env, (isset($context['resultsHeading']) || array_key_exists('resultsHeading', $context) ? $context['resultsHeading'] : (function () {
                throw new RuntimeError('Variable "resultsHeading" does not exist.', 29, $this->source);
            })()), 'html', null, true);
            echo '</h2>
            <ul class="cs-search-results"></ul>
        </div>
        <div class="cs-forms">
            <form class="cs-search-form" action="';
            // line 33
            echo twig_escape_filter($this->env, (isset($context['formAction']) || array_key_exists('formAction', $context) ? $context['formAction'] : (function () {
                throw new RuntimeError('Variable "formAction" does not exist.', 33, $this->source);
            })()), 'html', null, true);
            echo '" method="get" target="_blank" rel="noopener">
                <div class="cs-search-params"></div>
                ';
            // line 35
            ob_start();
            // line 36
            echo '                    <button type="submit" class="btn submit fullwidth disabled">';
            echo twig_escape_filter($this->env, (isset($context['submitText']) || array_key_exists('submitText', $context) ? $context['submitText'] : (function () {
                throw new RuntimeError('Variable "submitText" does not exist.', 36, $this->source);
            })()), 'html', null, true);
            echo '</button>
                    ';
            // line 37
            if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
                throw new RuntimeError('Variable "CraftEdition" does not exist.', 37, $this->source);
            })()) == (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
                throw new RuntimeError('Variable "CraftPro" does not exist.', 37, $this->source);
            })()))) {
                // line 38
                echo '                        <p>';
                echo $this->extensions['craft\web\twig\Extension']->translateFilter('or <a>send to Developer Support</a>', 'app');
                echo '</p>
                    ';
            }
            // line 40
            echo '                    ';
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['class' => 'btn fullwidth cancel', 'type' => 'button', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Cancel', 'app')]);
            // line 44
            echo '
                ';
            echo craft\helpers\Html::tag('div', ob_get_clean(), ['class' => 'cs-button-wrapper']);
            // line 46
            echo '                <hr>
                <h3 class="cs-more-resources-heading">';
            // line 47
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('More Resources', 'app'), 'html', null, true);
            echo '</h3>
                <div class="cs-logo-resources">
                    ';
            // line 49
            echo twig_call_macro($macros['_self'], 'macro_resourceLink', [['link' => 'https://craftcms.com/partners', 'iconPath' => '/logos/craft-partners.svg', 'title' => 'Craft Partners', 'description' => $this->extensions['craft\web\twig\Extension']->translateFilter('Find an official Craft Partner', 'app'), 'bundleUrl' =>             // line 54
(isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
    throw new RuntimeError('Variable "bundleUrl" does not exist.', 54, $this->source);
})()), ]], 49, $context, $this->getSourceContext());
            // line 55
            echo '
                    ';
            // line 56
            echo twig_call_macro($macros['_self'], 'macro_resourceLink', [['link' => 'https://craftcms.com/discord', 'iconPath' => '/logos/discord.svg', 'title' => 'Discord', 'description' => $this->extensions['craft\web\twig\Extension']->translateFilter('Meet the Craft community', 'app'), 'bundleUrl' =>             // line 61
(isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
    throw new RuntimeError('Variable "bundleUrl" does not exist.', 61, $this->source);
})()), ]], 56, $context, $this->getSourceContext());
            // line 62
            echo '
                    ';
            // line 63
            echo twig_call_macro($macros['_self'], 'macro_resourceLink', [['link' => 'https://craftquest.io', 'iconPath' => '/logos/craftquest.svg', 'title' => 'CraftQuest', 'description' => $this->extensions['craft\web\twig\Extension']->translateFilter('Unlimited video training', 'app'), 'bundleUrl' =>             // line 68
(isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
    throw new RuntimeError('Variable "bundleUrl" does not exist.', 68, $this->source);
})()), ]], 63, $context, $this->getSourceContext());
            // line 69
            echo '
                </div>
                <div class="cs-icon-resources">
                    ';
            // line 72
            ob_start();
            // line 73
            echo '                        ';
            echo craft\helpers\Cp::iconSvg('book');
            echo '
                        <span>';
            // line 74
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Documentation', 'app'), 'html', null, true);
            echo '</span>
                    ';
            $context['documentationLinkHtml'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 76
            echo '                    ';
            ob_start();
            // line 77
            echo '                        ';
            echo craft\helpers\Cp::iconSvg('magnifying-glass');
            echo '
                        <span>';
            // line 78
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Knowledge Base', 'app'), 'html', null, true);
            echo '</span>
                    ';
            $context['knowledgeBaseLinkHtml'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 80
            echo '                    ';
            echo twig_call_macro($macros['links'], 'macro_externalLink', [['link' => 'https://craftcms.com/docs/4.x/', 'html' =>             // line 82
(isset($context['documentationLinkHtml']) || array_key_exists('documentationLinkHtml', $context) ? $context['documentationLinkHtml'] : (function () {
    throw new RuntimeError('Variable "documentationLinkHtml" does not exist.', 82, $this->source);
})()), ]], 80, $context, $this->getSourceContext());
            // line 83
            echo '
                    ';
            // line 84
            echo twig_call_macro($macros['links'], 'macro_externalLink', [['link' => 'https://craftcms.com/knowledge-base', 'html' =>             // line 86
(isset($context['knowledgeBaseLinkHtml']) || array_key_exists('knowledgeBaseLinkHtml', $context) ? $context['knowledgeBaseLinkHtml'] : (function () {
    throw new RuntimeError('Variable "knowledgeBaseLinkHtml" does not exist.', 86, $this->source);
})()), ]], 84, $context, $this->getSourceContext());
            // line 87
            echo '
                </div>
            </form>
            <form class="cs-support-form hidden" action="';
            // line 90
            echo twig_escape_filter($this->env, craft\helpers\UrlHelper::actionUrl('dashboard/send-support-request'), 'html', null, true);
            echo '" method="post" target="';
            echo twig_escape_filter($this->env, (isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 90, $this->source);
            })()), 'html', null, true);
            echo '-iframe" accept-charset="UTF-8" enctype="multipart/form-data">
                ';
            // line 91
            echo craft\helpers\Html::csrfInput();
            echo '
                ';
            // line 92
            echo craft\helpers\Html::hiddenInput('widgetId', craft\helpers\Template::attribute($this->env, $this->source, (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 92, $this->source);
            })()), 'id', []));
            echo '
                ';
            // line 93
            echo craft\helpers\Html::hiddenInput('message', '', ['class' => 'cs-support-message']);
            echo '

                ';
            // line 95
            $context['email'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                throw new RuntimeError('Variable "currentUser" does not exist.', 95, $this->source);
            })()), 'email', []);
            // line 96
            echo '                ';
            if (twig_in_filter((isset($context['email']) || array_key_exists('email', $context) ? $context['email'] : (function () {
                throw new RuntimeError('Variable "email" does not exist.', 96, $this->source);
            })()), [0 => 'support@pixelandtonic.com', 1 => 'support@craftcms.com'])) {
                // line 97
                echo '                    ';
                $context['email'] = '';
                // line 98
                echo '                ';
            }
            // line 99
            echo '
                ';
            // line 100
            echo twig_call_macro($macros['forms'], 'macro_textField', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Your Email', 'app'), 'name' => 'fromEmail', 'value' =>             // line 104
(isset($context['email']) || array_key_exists('email', $context) ? $context['email'] : (function () {
    throw new RuntimeError('Variable "email" does not exist.', 104, $this->source);
})()), ]], 100, $context, $this->getSourceContext());
            // line 105
            echo '

                <a class="fieldtoggle" data-target="';
            // line 107
            echo twig_escape_filter($this->env, (isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 107, $this->source);
            })()), 'html', null, true);
            echo '-support-more">';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('More', 'app'), 'html', null, true);
            echo '</a>

                <div id="';
            // line 109
            echo twig_escape_filter($this->env, (isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 109, $this->source);
            })()), 'html', null, true);
            echo '-support-more" class="cs-support-more hidden">
                    <fieldset>
                        ';
            // line 111
            echo twig_call_macro($macros['forms'], 'macro_checkboxField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Attach error logs', 'app'), 'name' => 'attachLogs', 'checked' => true]], 111, $context, $this->getSourceContext());
            // line 115
            echo '

                        ';
            // line 117
            if ((isset($context['showBackupOption']) || array_key_exists('showBackupOption', $context) ? $context['showBackupOption'] : (function () {
                throw new RuntimeError('Variable "showBackupOption" does not exist.', 117, $this->source);
            })())) {
                // line 118
                echo '                            ';
                echo twig_call_macro($macros['forms'], 'macro_checkboxField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Attach a database backup', 'app'), 'name' => 'attachDbBackup', 'checked' => true]], 118, $context, $this->getSourceContext());
                // line 122
                echo '
                        ';
            }
            // line 124
            echo '
                        ';
            // line 125
            echo twig_call_macro($macros['forms'], 'macro_checkboxField', [['name' => 'attachTemplates', 'checked' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Include your template files', 'app')]], 125, $context, $this->getSourceContext());
            // line 129
            echo '
                    </fieldset>

                    ';
            // line 132
            echo twig_call_macro($macros['forms'], 'macro_fileField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Attach an additional file', 'app'), 'class' => 'cs-support-attachment', 'name' => 'attachAdditionalFile']], 132, $context, $this->getSourceContext());
            // line 136
            echo '
                </div>

                ';
            // line 139
            echo twig_call_macro($macros['forms'], 'macro_submitButton', [['class' => [0 => 'fullwidth', 1 => 'disabled'], 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Send', 'app'), 'spinner' => true]], 139, $context, $this->getSourceContext());
            // line 143
            echo '
            </form>
        </div>
        <iframe id="';
            // line 146
            echo twig_escape_filter($this->env, (isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 146, $this->source);
            })()), 'html', null, true);
            echo '-iframe" name="';
            echo twig_escape_filter($this->env, (isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 146, $this->source);
            })()), 'html', null, true);
            echo '-iframe" class="hidden"></iframe>
    </div>
';
            craft\helpers\Template::endProfile('macro', 'screen');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_components/widgets/CraftSupport/body.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [399 => 146,  394 => 143,  392 => 139,  387 => 136,  385 => 132,  380 => 129,  378 => 125,  375 => 124,  371 => 122,  368 => 118,  366 => 117,  362 => 115,  360 => 111,  355 => 109,  348 => 107,  344 => 105,  342 => 104,  341 => 100,  338 => 99,  335 => 98,  332 => 97,  329 => 96,  327 => 95,  322 => 93,  318 => 92,  314 => 91,  308 => 90,  303 => 87,  301 => 86,  300 => 84,  297 => 83,  295 => 82,  293 => 80,  288 => 78,  283 => 77,  280 => 76,  275 => 74,  270 => 73,  268 => 72,  263 => 69,  261 => 68,  260 => 63,  257 => 62,  255 => 61,  254 => 56,  251 => 55,  249 => 54,  248 => 49,  243 => 47,  240 => 46,  236 => 44,  233 => 40,  227 => 38,  225 => 37,  220 => 36,  218 => 35,  213 => 33,  206 => 29,  202 => 28,  198 => 26,  196 => 24,  195 => 21,  192 => 20,  190 => 18,  189 => 17,  181 => 16,  178 => 15,  175 => 14,  172 => 13,  150 => 12,  135 => 8,  127 => 6,  121 => 4,  107 => 3,  101 => 188,  99 => 181,  98 => 180,  97 => 179,  96 => 178,  92 => 176,  90 => 169,  89 => 168,  88 => 167,  87 => 166,  80 => 162,  76 => 161,  72 => 160,  66 => 157,  62 => 156,  58 => 155,  52 => 151,  50 => 150,  47 => 149,  44 => 11,  41 => 2,  39 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import \"_includes/links\" as links %}

{% macro resourceLink(config) %}
    <a href=\"{{ config.link }}\" target=\"_blank\" rel=\"noopener\">
        <h4 class=\"cs-resource-heading\">
            <img class=\"cs-logo-image\" src=\"{{ config.bundleUrl }}{{ config.iconPath }}\" alt=\"{{ config.title }}\">
        </h4>
        <p>{{ config.description }} {{ links.externalLinkIcon() }}</p>
    </a>
{% endmacro %}

{% macro screen(widget, showBackupOption, bundleUrl, screen, placeholder, resultsIcon, resultsHeading, formAction, submitText) %}
    {% import \"_includes/forms\" as forms %}
    {% set idPrefix = 'cs-'~screen~random() %}

    <div id=\"cs-screen-{{ screen }}\" class=\"cs-screen cs-screen-2 cs-screen-{{ screen }}\" action=\"{{ formAction }}\" method=\"get\" target=\"_blank\" rel=\"noopener\">
        {{ tag('h2', {
            text: submitText,
            class: 'cs-heading'
        }) }}
        {{ forms.textareaField({
            first: true,
            class: 'cs-body-text',
            label: placeholder|t('app'),
            rows: 5
        }) }}
        <div class=\"cs-search-results-container hidden\">
            <div class=\"cs-search-icon\">{{ svg(resultsIcon, sanitize=false) }}</div>
            <h2>{{ resultsHeading }}</h2>
            <ul class=\"cs-search-results\"></ul>
        </div>
        <div class=\"cs-forms\">
            <form class=\"cs-search-form\" action=\"{{ formAction }}\" method=\"get\" target=\"_blank\" rel=\"noopener\">
                <div class=\"cs-search-params\"></div>
                {% tag 'div' with { class: 'cs-button-wrapper' }%}
                    <button type=\"submit\" class=\"btn submit fullwidth disabled\">{{ submitText }}</button>
                    {% if CraftEdition == CraftPro %}
                        <p>{{ \"or <a>send to Developer Support</a>\"|t('app')|raw }}</p>
                    {% endif %}
                    {{ tag('button', {
                        class: 'btn fullwidth cancel',
                        type: 'button',
                        text: 'Cancel'|t('app'),
                    }) }}
                {% endtag %}
                <hr>
                <h3 class=\"cs-more-resources-heading\">{{ 'More Resources'|t('app') }}</h3>
                <div class=\"cs-logo-resources\">
                    {{ _self.resourceLink({
                        link: 'https://craftcms.com/partners',
                        iconPath: '/logos/craft-partners.svg',
                        title: 'Craft Partners',
                        description: 'Find an official Craft Partner'|t('app'),
                        bundleUrl: bundleUrl,
                    }) }}
                    {{ _self.resourceLink({
                        link: 'https://craftcms.com/discord',
                        iconPath: '/logos/discord.svg',
                        title: 'Discord',
                        description: 'Meet the Craft community'|t('app'),
                        bundleUrl: bundleUrl,
                    }) }}
                    {{ _self.resourceLink({
                        link: 'https://craftquest.io',
                        iconPath: '/logos/craftquest.svg',
                        title: 'CraftQuest',
                        description: 'Unlimited video training'|t('app'),
                        bundleUrl: bundleUrl,
                    }) }}
                </div>
                <div class=\"cs-icon-resources\">
                    {% set documentationLinkHtml %}
                        {{ iconSvg('book') }}
                        <span>{{ 'Documentation'|t('app') }}</span>
                    {% endset %}
                    {% set knowledgeBaseLinkHtml %}
                        {{ iconSvg('magnifying-glass') }}
                        <span>{{ 'Knowledge Base'|t('app') }}</span>
                    {% endset %}
                    {{ links.externalLink({
                        link: 'https://craftcms.com/docs/4.x/',
                        html: documentationLinkHtml
                    }) }}
                    {{ links.externalLink({
                        link: 'https://craftcms.com/knowledge-base',
                        html: knowledgeBaseLinkHtml
                    }) }}
                </div>
            </form>
            <form class=\"cs-support-form hidden\" action=\"{{ actionUrl('dashboard/send-support-request') }}\" method=\"post\" target=\"{{ idPrefix }}-iframe\" accept-charset=\"UTF-8\" enctype=\"multipart/form-data\">
                {{ csrfInput() }}
                {{ hiddenInput('widgetId', widget.id) }}
                {{ hiddenInput('message', '', {class: 'cs-support-message'}) }}

                {% set email = currentUser.email %}
                {% if email in ['support@pixelandtonic.com', 'support@craftcms.com'] %}
                    {% set email = '' %}
                {% endif %}

                {{ forms.textField({
                    first: true,
                    label: \"Your Email\"|t('app'),
                    name: 'fromEmail',
                    value: email
                }) }}

                <a class=\"fieldtoggle\" data-target=\"{{ idPrefix }}-support-more\">{{ \"More\"|t('app') }}</a>

                <div id=\"{{ idPrefix }}-support-more\" class=\"cs-support-more hidden\">
                    <fieldset>
                        {{ forms.checkboxField({
                            label: 'Attach error logs'|t('app'),
                            name: 'attachLogs',
                            checked: true
                        }) }}

                        {% if showBackupOption %}
                            {{ forms.checkboxField({
                                label: 'Attach a database backup'|t('app'),
                                name: 'attachDbBackup',
                                checked: true
                            }) }}
                        {% endif %}

                        {{ forms.checkboxField({
                            name: 'attachTemplates',
                            checked: true,
                            label: 'Include your template files'|t('app'),
                        }) }}
                    </fieldset>

                    {{ forms.fileField({
                        label: 'Attach an additional file'|t('app'),
                        class: 'cs-support-attachment',
                        name: 'attachAdditionalFile',
                    }) }}
                </div>

                {{ forms.submitButton({
                    class: ['fullwidth', 'disabled'],
                    label: 'Send'|t('app'),
                    spinner: true,
                }) }}
            </form>
        </div>
        <iframe id=\"{{ idPrefix }}-iframe\" name=\"{{ idPrefix }}-iframe\" class=\"hidden\"></iframe>
    </div>
{% endmacro %}

{% from _self import screen %}


<div class=\"cs-screen cs-screen-home\">
    <button type=\"button\" class=\"cs-opt\" data-screen=\"help\" aria-controls=\"cs-screen-help\" aria-expanded=\"false\">
        <div class=\"cs-opt-icon\">{{ iconSvg('life-ring') }}</div>
        <h2>{{ \"Get help\"|t('app') }}</h2>
        <p>{{ \"How-to’s and other questions\"|t('app') }}</p>
    </button>
    <button type=\"button\" class=\"cs-opt\" data-screen=\"feedback\" aria-controls=\"cs-screen-feedback\" aria-expanded=\"false\">
        <div class=\"cs-opt-icon\">{{ iconSvg('bullhorn') }}</div>
        <h2>{{ \"Give feedback\"|t('app') }}</h2>
        <p>{{ \"Bug reports and feature requests\"|t('app') }}</p>
    </button>
</div>

{{ screen(
    widget,
    showBackupOption,
    bundleUrl,
    'help',
    'Briefly describe your question.'|t('app'),
    iconSvg('craft-stack-exchange'),
    'Similar questions on Stack Exchange'|t('app'),
    'https://craftcms.stackexchange.com/questions/ask',
    'Ask on Stack Exchange'|t('app'),
) }}

{{ screen(
    widget,
    showBackupOption,
    bundleUrl,
    'feedback',
    'Briefly describe your issue or idea.'|t('app'),
    iconSvg('github'),
    'Similar issues on GitHub'|t('app'),
    'https://github.com/craftcms/cms/issues/new',
    'Post on GitHub'|t('app'),
) }}
", '_components/widgets/CraftSupport/body.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/widgets/CraftSupport/body.twig');
    }
}
