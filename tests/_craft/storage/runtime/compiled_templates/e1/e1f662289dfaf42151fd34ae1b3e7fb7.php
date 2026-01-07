<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _components/widgets/CraftSupport/body.twig */
class __TwigTemplate_db87cb7bf2715de2b452240864ea6437 extends Template
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
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/widgets/CraftSupport/body.twig');
        // line 1
        $macros['links'] = $this->macros['links'] = $this->loadTemplate('_includes/links', '_components/widgets/CraftSupport/body.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 11
        yield '
';
        // line 149
        yield '
';
        // line 150
        $macros['__internal_parse_0'] = $this->macros['__internal_parse_0'] = $this;
        // line 151
        yield '

<div class="cs-screen cs-screen-home">
    <button type="button" class="cs-opt" data-screen="help" aria-controls="cs-screen-help" aria-expanded="false">
        <div class="cs-opt-icon">';
        // line 155
        yield craft\helpers\Cp::iconSvg('life-ring');
        yield '</div>
        <h2>';
        // line 156
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Get help', 'app'), 'html', null, true);
        yield '</h2>
        <p>';
        // line 157
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('How-to’s and other questions', 'app'), 'html', null, true);
        yield '</p>
    </button>
    <button type="button" class="cs-opt" data-screen="feedback" aria-controls="cs-screen-feedback" aria-expanded="false">
        <div class="cs-opt-icon">';
        // line 160
        yield craft\helpers\Cp::iconSvg('bullhorn');
        yield '</div>
        <h2>';
        // line 161
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Give feedback', 'app'), 'html', null, true);
        yield '</h2>
        <p>';
        // line 162
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Bug reports and feature requests', 'app'), 'html', null, true);
        yield '</p>
    </button>
</div>

';
        // line 166
        yield CoreExtension::callMacro($macros['__internal_parse_0'], 'macro_screen', [        // line 167
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 167, $this->source);
            })()),         // line 168
            (isset($context['showBackupOption']) || array_key_exists('showBackupOption', $context) ? $context['showBackupOption'] : (function () {
                throw new RuntimeError('Variable "showBackupOption" does not exist.', 168, $this->source);
            })()),         // line 169
            (isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
                throw new RuntimeError('Variable "bundleUrl" does not exist.', 169, $this->source);
            })()), 'help', $this->extensions['craft\web\twig\Extension']->translateFilter('Briefly describe your question.', 'app'), craft\helpers\Cp::iconSvg('craft-stack-exchange'), $this->extensions['craft\web\twig\Extension']->translateFilter('Similar questions on Stack Exchange', 'app'), 'https://craftcms.stackexchange.com/questions/ask', $this->extensions['craft\web\twig\Extension']->translateFilter('Ask on Stack Exchange', 'app')], 166, $context, $this->getSourceContext());
        // line 176
        yield '

';
        // line 178
        yield CoreExtension::callMacro($macros['__internal_parse_0'], 'macro_screen', [        // line 179
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 179, $this->source);
            })()),         // line 180
            (isset($context['showBackupOption']) || array_key_exists('showBackupOption', $context) ? $context['showBackupOption'] : (function () {
                throw new RuntimeError('Variable "showBackupOption" does not exist.', 180, $this->source);
            })()),         // line 181
            (isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
                throw new RuntimeError('Variable "bundleUrl" does not exist.', 181, $this->source);
            })()), 'feedback', $this->extensions['craft\web\twig\Extension']->translateFilter('Briefly describe your issue or idea.', 'app'), craft\helpers\Cp::iconSvg('github'), $this->extensions['craft\web\twig\Extension']->translateFilter('Similar issues on GitHub', 'app'), 'https://github.com/craftcms/cms/issues/new', $this->extensions['craft\web\twig\Extension']->translateFilter('Post on GitHub', 'app')], 178, $context, $this->getSourceContext());
        // line 188
        yield '
';
        craft\helpers\Template::endProfile('template', '_components/widgets/CraftSupport/body.twig');
        yield from [];
    }

    // line 3
    public function macro_resourceLink($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'resourceLink');
            // line 4
            yield '    <a href="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 4, $this->source);
            })()), 'link', [], 'any', false, false, false, 4), 'html', null, true);
            yield '" target="_blank" rel="noopener">
        <h4 class="cs-resource-heading">
            <img class="cs-logo-image" src="';
            // line 6
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 6, $this->source);
            })()), 'bundleUrl', [], 'any', false, false, false, 6), 'html', null, true);
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 6, $this->source);
            })()), 'iconPath', [], 'any', false, false, false, 6), 'html', null, true);
            yield '" alt="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 6, $this->source);
            })()), 'title', [], 'any', false, false, false, 6), 'html', null, true);
            yield '">
        </h4>
        <p>';
            // line 8
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 8, $this->source);
            })()), 'description', [], 'any', false, false, false, 8), 'html', null, true);
            yield ' ';
            yield CoreExtension::callMacro($macros['links'], 'macro_externalLinkIcon', [], 8, $context, $this->getSourceContext());
            yield '</p>
    </a>
';
            craft\helpers\Template::endProfile('macro', 'resourceLink');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 12
    public function macro_screen($__widget__ = null, $__showBackupOption__ = null, $__bundleUrl__ = null, $__screen__ = null, $__placeholder__ = null, $__resultsIcon__ = null, $__resultsHeading__ = null, $__formAction__ = null, $__submitText__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
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
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'screen');
            // line 13
            yield '    ';
            $macros['forms'] = $this->loadTemplate('_includes/forms', '_components/widgets/CraftSupport/body.twig', 13)->unwrap();
            // line 14
            yield '    ';
            $context['idPrefix'] = (('cs-'.(isset($context['screen']) || array_key_exists('screen', $context) ? $context['screen'] : (function () {
                throw new RuntimeError('Variable "screen" does not exist.', 14, $this->source);
            })())).Twig\Extension\CoreExtension::random($this->env->getCharset()));
            // line 15
            yield '
    <div id="cs-screen-';
            // line 16
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['screen']) || array_key_exists('screen', $context) ? $context['screen'] : (function () {
                throw new RuntimeError('Variable "screen" does not exist.', 16, $this->source);
            })()), 'html', null, true);
            yield '" class="cs-screen cs-screen-2 cs-screen-';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['screen']) || array_key_exists('screen', $context) ? $context['screen'] : (function () {
                throw new RuntimeError('Variable "screen" does not exist.', 16, $this->source);
            })()), 'html', null, true);
            yield '" action="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['formAction']) || array_key_exists('formAction', $context) ? $context['formAction'] : (function () {
                throw new RuntimeError('Variable "formAction" does not exist.', 16, $this->source);
            })()), 'html', null, true);
            yield '" method="get" target="_blank" rel="noopener">
        ';
            // line 17
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('h2', ['text' =>             // line 18
(isset($context['submitText']) || array_key_exists('submitText', $context) ? $context['submitText'] : (function () {
    throw new RuntimeError('Variable "submitText" does not exist.', 18, $this->source);
})()), 'class' => 'cs-heading']);
            // line 20
            yield '
        ';
            // line 21
            yield CoreExtension::callMacro($macros['forms'], 'macro_textareaField', [['first' => true, 'class' => 'cs-body-text', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter(            // line 24
                (isset($context['placeholder']) || array_key_exists('placeholder', $context) ? $context['placeholder'] : (function () {
                    throw new RuntimeError('Variable "placeholder" does not exist.', 24, $this->source);
                })()), 'app'), 'rows' => 5]], 21, $context, $this->getSourceContext());
            // line 26
            yield '
        <div class="cs-search-results-container hidden">
            <div class="cs-search-icon">';
            // line 28
            yield $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['resultsIcon']) || array_key_exists('resultsIcon', $context) ? $context['resultsIcon'] : (function () {
                throw new RuntimeError('Variable "resultsIcon" does not exist.', 28, $this->source);
            })()), false);
            yield '</div>
            <h2>';
            // line 29
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['resultsHeading']) || array_key_exists('resultsHeading', $context) ? $context['resultsHeading'] : (function () {
                throw new RuntimeError('Variable "resultsHeading" does not exist.', 29, $this->source);
            })()), 'html', null, true);
            yield '</h2>
            <ul class="cs-search-results"></ul>
        </div>
        <div class="cs-forms">
            <form class="cs-search-form" action="';
            // line 33
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['formAction']) || array_key_exists('formAction', $context) ? $context['formAction'] : (function () {
                throw new RuntimeError('Variable "formAction" does not exist.', 33, $this->source);
            })()), 'html', null, true);
            yield '" method="get" target="_blank" rel="noopener">
                <div class="cs-search-params"></div>
                ';
            // line 35
            ob_start();
            // line 36
            yield '                    <button type="submit" class="btn submit fullwidth disabled">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['submitText']) || array_key_exists('submitText', $context) ? $context['submitText'] : (function () {
                throw new RuntimeError('Variable "submitText" does not exist.', 36, $this->source);
            })()), 'html', null, true);
            yield '</button>
                    ';
            // line 37
            if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
                throw new RuntimeError('Variable "CraftEdition" does not exist.', 37, $this->source);
            })()) >= (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
                throw new RuntimeError('Variable "CraftPro" does not exist.', 37, $this->source);
            })()))) {
                // line 38
                yield '                        <p>';
                yield $this->extensions['craft\web\twig\Extension']->translateFilter('or <a>send to Developer Support</a>', 'app');
                yield '</p>
                    ';
            }
            // line 40
            yield '                    ';
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['class' => 'btn fullwidth cancel', 'type' => 'button', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Cancel', 'app')]);
            // line 44
            yield '
                ';
            echo craft\helpers\Html::tag('div', ob_get_clean(), ['class' => 'cs-button-wrapper']);
            // line 46
            yield '                <hr>
                <h3 class="cs-more-resources-heading">';
            // line 47
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('More Resources', 'app'), 'html', null, true);
            yield '</h3>
                <div class="cs-logo-resources">
                    ';
            // line 49
            yield CoreExtension::callMacro($macros['_self'], 'macro_resourceLink', [['link' => 'https://craftcms.com/partners', 'iconPath' => '/logos/craft-partners.svg', 'title' => 'Craft Partners', 'description' => $this->extensions['craft\web\twig\Extension']->translateFilter('Find an official Craft Partner', 'app'), 'bundleUrl' =>             // line 54
(isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
    throw new RuntimeError('Variable "bundleUrl" does not exist.', 54, $this->source);
})())]], 49, $context, $this->getSourceContext());
            // line 55
            yield '
                    ';
            // line 56
            yield CoreExtension::callMacro($macros['_self'], 'macro_resourceLink', [['link' => 'https://craftcms.com/discord', 'iconPath' => '/logos/discord.svg', 'title' => 'Discord', 'description' => $this->extensions['craft\web\twig\Extension']->translateFilter('Meet the Craft community', 'app'), 'bundleUrl' =>             // line 61
(isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
    throw new RuntimeError('Variable "bundleUrl" does not exist.', 61, $this->source);
})())]], 56, $context, $this->getSourceContext());
            // line 62
            yield '
                    ';
            // line 63
            yield CoreExtension::callMacro($macros['_self'], 'macro_resourceLink', [['link' => 'https://craftquest.io', 'iconPath' => '/logos/craftquest.svg', 'title' => 'CraftQuest', 'description' => $this->extensions['craft\web\twig\Extension']->translateFilter('Unlimited video training', 'app'), 'bundleUrl' =>             // line 68
(isset($context['bundleUrl']) || array_key_exists('bundleUrl', $context) ? $context['bundleUrl'] : (function () {
    throw new RuntimeError('Variable "bundleUrl" does not exist.', 68, $this->source);
})())]], 63, $context, $this->getSourceContext());
            // line 69
            yield '
                </div>
                <div class="cs-icon-resources">
                    ';
            // line 72
            $context['documentationLinkHtml'] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () {
                // line 73
                yield '                        ';
                yield craft\helpers\Cp::iconSvg('book');
                yield '
                        <span>';
                // line 74
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Documentation', 'app'), 'html', null, true);
                yield '</span>
                    ';
                yield from [];
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 76
            yield '                    ';
            $context['knowledgeBaseLinkHtml'] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () {
                // line 77
                yield '                        ';
                yield craft\helpers\Cp::iconSvg('magnifying-glass');
                yield '
                        <span>';
                // line 78
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Knowledge Base', 'app'), 'html', null, true);
                yield '</span>
                    ';
                yield from [];
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 80
            yield '                    ';
            yield CoreExtension::callMacro($macros['links'], 'macro_externalLink', [['link' => 'https://craftcms.com/docs/5.x/', 'html' =>             // line 82
(isset($context['documentationLinkHtml']) || array_key_exists('documentationLinkHtml', $context) ? $context['documentationLinkHtml'] : (function () {
    throw new RuntimeError('Variable "documentationLinkHtml" does not exist.', 82, $this->source);
})())]], 80, $context, $this->getSourceContext());
            // line 83
            yield '
                    ';
            // line 84
            yield CoreExtension::callMacro($macros['links'], 'macro_externalLink', [['link' => 'https://craftcms.com/knowledge-base', 'html' =>             // line 86
(isset($context['knowledgeBaseLinkHtml']) || array_key_exists('knowledgeBaseLinkHtml', $context) ? $context['knowledgeBaseLinkHtml'] : (function () {
    throw new RuntimeError('Variable "knowledgeBaseLinkHtml" does not exist.', 86, $this->source);
})())]], 84, $context, $this->getSourceContext());
            // line 87
            yield '
                </div>
            </form>
            <form class="cs-support-form hidden" action="';
            // line 90
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::actionUrl('dashboard/send-support-request'), 'html', null, true);
            yield '" method="post" target="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 90, $this->source);
            })()), 'html', null, true);
            yield '-iframe" accept-charset="UTF-8" enctype="multipart/form-data">
                ';
            // line 91
            yield craft\helpers\Html::csrfInput();
            yield '
                ';
            // line 92
            yield craft\helpers\Html::hiddenInput('widgetId', craft\helpers\Template::attribute($this->env, $this->source, (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 92, $this->source);
            })()), 'id', [], 'any', false, false, false, 92));
            yield '
                ';
            // line 93
            yield craft\helpers\Html::hiddenInput('message', '', ['class' => 'cs-support-message']);
            yield '

                ';
            // line 95
            $context['email'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                throw new RuntimeError('Variable "currentUser" does not exist.', 95, $this->source);
            })()), 'email', [], 'any', false, false, false, 95);
            // line 96
            yield '                ';
            if (CoreExtension::inFilter((isset($context['email']) || array_key_exists('email', $context) ? $context['email'] : (function () {
                throw new RuntimeError('Variable "email" does not exist.', 96, $this->source);
            })()), ['support@pixelandtonic.com', 'support@craftcms.com'])) {
                // line 97
                yield '                    ';
                $context['email'] = '';
                // line 98
                yield '                ';
            }
            // line 99
            yield '
                ';
            // line 100
            yield CoreExtension::callMacro($macros['forms'], 'macro_textField', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Your Email', 'app'), 'name' => 'fromEmail', 'value' =>             // line 104
(isset($context['email']) || array_key_exists('email', $context) ? $context['email'] : (function () {
    throw new RuntimeError('Variable "email" does not exist.', 104, $this->source);
})())]], 100, $context, $this->getSourceContext());
            // line 105
            yield '

                <a class="fieldtoggle" data-target="';
            // line 107
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 107, $this->source);
            })()), 'html', null, true);
            yield '-support-more">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('More', 'app'), 'html', null, true);
            yield '</a>

                <div id="';
            // line 109
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 109, $this->source);
            })()), 'html', null, true);
            yield '-support-more" class="cs-support-more hidden">
                    <fieldset>
                        ';
            // line 111
            yield CoreExtension::callMacro($macros['forms'], 'macro_checkboxField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Attach error logs', 'app'), 'name' => 'attachLogs', 'checked' => true]], 111, $context, $this->getSourceContext());
            // line 115
            yield '

                        ';
            // line 117
            if ((isset($context['showBackupOption']) || array_key_exists('showBackupOption', $context) ? $context['showBackupOption'] : (function () {
                throw new RuntimeError('Variable "showBackupOption" does not exist.', 117, $this->source);
            })())) {
                // line 118
                yield '                            ';
                yield CoreExtension::callMacro($macros['forms'], 'macro_checkboxField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Attach a database backup', 'app'), 'name' => 'attachDbBackup', 'checked' => true]], 118, $context, $this->getSourceContext());
                // line 122
                yield '
                        ';
            }
            // line 124
            yield '
                        ';
            // line 125
            yield CoreExtension::callMacro($macros['forms'], 'macro_checkboxField', [['name' => 'attachTemplates', 'checked' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Include your template files', 'app')]], 125, $context, $this->getSourceContext());
            // line 129
            yield '
                    </fieldset>

                    ';
            // line 132
            yield CoreExtension::callMacro($macros['forms'], 'macro_fileField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Attach an additional file', 'app'), 'class' => 'cs-support-attachment', 'name' => 'attachAdditionalFile']], 132, $context, $this->getSourceContext());
            // line 136
            yield '
                </div>

                ';
            // line 139
            yield CoreExtension::callMacro($macros['forms'], 'macro_submitButton', [['class' => ['fullwidth', 'disabled'], 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Send', 'app'), 'spinner' => true]], 139, $context, $this->getSourceContext());
            // line 143
            yield '
            </form>
        </div>
        <iframe id="';
            // line 146
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 146, $this->source);
            })()), 'html', null, true);
            yield '-iframe" name="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 146, $this->source);
            })()), 'html', null, true);
            yield '-iframe" class="hidden"></iframe>
    </div>
';
            craft\helpers\Template::endProfile('macro', 'screen');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/widgets/CraftSupport/body.twig';
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
        return [402 => 146,  397 => 143,  395 => 139,  390 => 136,  388 => 132,  383 => 129,  381 => 125,  378 => 124,  374 => 122,  371 => 118,  369 => 117,  365 => 115,  363 => 111,  358 => 109,  351 => 107,  347 => 105,  345 => 104,  344 => 100,  341 => 99,  338 => 98,  335 => 97,  332 => 96,  330 => 95,  325 => 93,  321 => 92,  317 => 91,  311 => 90,  306 => 87,  304 => 86,  303 => 84,  300 => 83,  298 => 82,  296 => 80,  290 => 78,  285 => 77,  282 => 76,  276 => 74,  271 => 73,  269 => 72,  264 => 69,  262 => 68,  261 => 63,  258 => 62,  256 => 61,  255 => 56,  252 => 55,  250 => 54,  249 => 49,  244 => 47,  241 => 46,  237 => 44,  234 => 40,  228 => 38,  226 => 37,  221 => 36,  219 => 35,  214 => 33,  207 => 29,  203 => 28,  199 => 26,  197 => 24,  196 => 21,  193 => 20,  191 => 18,  190 => 17,  182 => 16,  179 => 15,  176 => 14,  173 => 13,  152 => 12,  140 => 8,  132 => 6,  126 => 4,  113 => 3,  106 => 188,  104 => 181,  103 => 180,  102 => 179,  101 => 178,  97 => 176,  95 => 169,  94 => 168,  93 => 167,  92 => 166,  85 => 162,  81 => 161,  77 => 160,  71 => 157,  67 => 156,  63 => 155,  57 => 151,  55 => 150,  52 => 149,  49 => 11,  46 => 2,  44 => 1];
    }

    public function getSourceContext(): Source
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
                    {% if CraftEdition >= CraftPro %}
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
                        link: 'https://craftcms.com/docs/5.x/',
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
", '_components/widgets/CraftSupport/body.twig', '/tmp/packages/craft5/src/templates/_components/widgets/CraftSupport/body.twig');
    }
}
