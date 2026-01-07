<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/utilities/ProjectConfig.twig */
class __TwigTemplate_9213b556f0c1fb8c90a6e15a2f11ad77 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/ProjectConfig.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/ProjectConfig.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 3
        if ((isset($context['yamlExists']) || array_key_exists('yamlExists', $context) ? $context['yamlExists'] : (function () {
            throw new RuntimeError('Variable "yamlExists" does not exist.', 3, $this->source);
        })())) {
            // line 4
            yield '    <form method="post" action="" accept-charset="UTF-8">
        ';
            // line 5
            if ((isset($context['invert']) || array_key_exists('invert', $context) ? $context['invert'] : (function () {
                throw new RuntimeError('Variable "invert" does not exist.', 5, $this->source);
            })())) {
                // line 6
                yield '            <h2>';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Update YAML Files', 'app'), 'html', null, true);
                yield '</h2>
            <p>';
                // line 7
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Update your project config YAML files to reflect the latest changes in the loaded project config.', 'app'), 'html', null, true);
                yield '</p>
        ';
            } else {
                // line 9
                yield '            <h2>';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Apply YAML Changes', 'app'), 'html', null, true);
                yield '</h2>
            <p>';
                // line 10
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Apply changes in your project config YAML files to the loaded project config.', 'app'), 'html', null, true);
                yield '</p>
        ';
            }
            // line 12
            yield '        ';
            yield craft\helpers\Html::csrfInput();
            yield '
        ';
            // line 13
            if ((isset($context['areChangesPending']) || array_key_exists('areChangesPending', $context) ? $context['areChangesPending'] : (function () {
                throw new RuntimeError('Variable "areChangesPending" does not exist.', 13, $this->source);
            })())) {
                // line 14
                yield '            <div id="diff" class="pane loading" tabindex="0">
                <div class="spinner spinner-absolute"></div>
            </div>
            <div class="readable">
                <blockquote class="note $noteClass">
                    <p>
                        ';
                // line 24
                ((                // line 20
                    (isset($context['invert']) || array_key_exists('invert', $context) ? $context['invert'] : (function () {
                        throw new RuntimeError('Variable "invert" does not exist.', 20, $this->source);
                    })())) ? (yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Make sure you’re not overwriting changes in the YAML files that were made on another environment.', 'app'), 'html', null, true)) : (yield $this->extensions['craft\web\twig\Extension']->translateFilter('Make sure you’ve followed the <a href="{url}" target="_blank">Environment Setup</a> instructions before applying project config YAML changes.', 'app', ['url' => 'https://craftcms.com/docs/5.x/system/project-config.html#environment-setup'])));
                // line 25
                yield '
                    </p>
                </blockquote>
            </div>
            <div class="buttons">
                ';
                // line 30
                if ((isset($context['invert']) || array_key_exists('invert', $context) ? $context['invert'] : (function () {
                    throw new RuntimeError('Variable "invert" does not exist.', 30, $this->source);
                })())) {
                    // line 31
                    yield '                    ';
                    yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => ['btn', 'formsubmit', 'secondary'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Update YAML files', 'app'), 'data' => ['action' => 'project-config/discard', 'confirm' => $this->extensions['craft\web\twig\Extension']->translateFilter('Are you sure you want to discard the pending project config YAML changes?', 'app')]]);
                    // line 39
                    yield '
                    ';
                    // line 40
                    yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => ['btn', 'formsubmit'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Apply YAML changes', 'app'), 'data' => ['action' => 'config-sync', 'params' => ['return' => 'utilities/project-config']]]);
                    // line 50
                    yield '
                ';
                } else {
                    // line 52
                    yield '                    ';
                    yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => ['btn', 'formsubmit', 'secondary'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Apply changes only', 'app'), 'data' => ['action' => 'config-sync', 'params' => ['return' => 'utilities/project-config']]]);
                    // line 62
                    yield '
                    ';
                    // line 63
                    yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => ['btn', 'formsubmit'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Reapply everything', 'app'), 'data' => ['action' => 'config-sync', 'params' => ['return' => 'utilities/project-config', 'force' => 1]]]);
                    // line 74
                    yield '
                    ';
                    // line 75
                    if (! (isset($context['readOnly']) || array_key_exists('readOnly', $context) ? $context['readOnly'] : (function () {
                        throw new RuntimeError('Variable "readOnly" does not exist.', 75, $this->source);
                    })())) {
                        // line 76
                        yield '                        ';
                        yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => ['btn', 'formsubmit'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Discard changes', 'app'), 'data' => ['action' => 'project-config/discard', 'confirm' => $this->extensions['craft\web\twig\Extension']->translateFilter('Are you sure you want to discard the pending project config YAML changes?', 'app')]]);
                        // line 84
                        yield '
                    ';
                    }
                    // line 86
                    yield '                ';
                }
                // line 87
                yield '            </div>
        ';
            } else {
                // line 89
                yield '            <div class="pane">
                <p>
                    <span class="checkmark-icon"></span>
                    ';
                // line 92
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('There aren’t any pending project config changes to apply.', 'app'), 'html', null, true);
                yield '
                </p>
            </div>
            <div class="buttons">
                ';
                // line 96
                yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => ['btn', 'formsubmit'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Reapply everything', 'app'), 'data' => ['action' => 'config-sync', 'params' => ['return' => 'utilities/project-config', 'force' => 1]]]);
                // line 107
                yield '
            </div>
        ';
            }
            // line 110
            yield '    </form>
';
        } else {
            // line 112
            yield '    <form method="post" action="" accept-charset="UTF-8">
        <h2>';
            // line 113
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Generate YAML Files', 'app'), 'html', null, true);
            yield '</h2>
        <p>
            ';
            // line 115
            yield $this->extensions['craft\web\twig\Extension']->translateFilter('Save the loaded project config data to YAML files in your <code>{folder}</code> folder.', 'app', ['folder' => 'config/project/']);
            // line 117
            yield '
        </p>
        ';
            // line 119
            yield craft\helpers\Html::actionInput('project-config/discard');
            yield '
        ';
            // line 120
            yield craft\helpers\Html::csrfInput();
            yield '
        <div class="buttons">
            <button type="submit" class="btn secondary">';
            // line 122
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Generate', 'app'), 'html', null, true);
            yield '</button>
        </div>
    </form>
';
        }
        // line 126
        yield '
';
        // line 127
        if (! (isset($context['readOnly']) || array_key_exists('readOnly', $context) ? $context['readOnly'] : (function () {
            throw new RuntimeError('Variable "readOnly" does not exist.', 127, $this->source);
        })())) {
            // line 128
            yield '    <hr>

    <form method="post" action="" accept-charset="UTF-8">
        <h2>';
            // line 131
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Rebuild the Config', 'app'), 'html', null, true);
            yield '</h2>
        <p>';
            // line 132
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Rebuild the project config based on the data stored throughout the database.', 'app'), 'html', null, true);
            yield '</p>
        ';
            // line 133
            yield craft\helpers\Html::actionInput('project-config/rebuild');
            yield '
        ';
            // line 134
            yield craft\helpers\Html::csrfInput();
            yield '
        <div class="buttons">
            <button type="submit" class="btn secondary">';
            // line 136
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Rebuild', 'app'), 'html', null, true);
            yield '</button>
        </div>
    </form>
';
        }
        // line 140
        yield '
<hr>

<h2>';
        // line 143
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Loaded Project Config Data', 'app'), 'html', null, true);
        yield '</h2>
<div class="pane" tabindex="0">
    <pre><code>';
        // line 145
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['entireConfig']) || array_key_exists('entireConfig', $context) ? $context['entireConfig'] : (function () {
            throw new RuntimeError('Variable "entireConfig" does not exist.', 145, $this->source);
        })()), 'html', null, true);
        yield '</code></pre>
</div>
<div class="buttons">
    <button type="button" id="download" class="btn" data-icon="download">';
        // line 148
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Download', 'app'), 'html', null, true);
        yield '</button>
    <div id="download-spinner" class="spinner hidden"></div>
</div>

';
        // line 152
        ob_start();
        // line 153
        yield '    .pane {
        max-height: 500px;
        overflow: auto;
    }
    .pane pre {
        margin: 0;
        padding: 0;
        background-color: transparent;
    }
';
        craft\helpers\Template::css(ob_get_clean());
        // line 163
        yield '
';
        // line 164
        if ((isset($context['areChangesPending']) || array_key_exists('areChangesPending', $context) ? $context['areChangesPending'] : (function () {
            throw new RuntimeError('Variable "areChangesPending" does not exist.', 164, $this->source);
        })())) {
            // line 165
            yield '    ';
            ob_start();
            // line 166
            yield "        let cancelToken = axios.CancelToken.source();
        Craft.sendActionRequest('GET', Craft.getActionUrl('project-config/diff', {
            invert: ";
            // line 168
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['invert']) || array_key_exists('invert', $context) ? $context['invert'] : (function () {
                throw new RuntimeError('Variable "invert" does not exist.', 168, $this->source);
            })())), 'html', null, true);
            yield ",
        }), {
            cancelToken: cancelToken.token,
        }).then(response => {
            cancelToken = null;
            let maxLines = 20;
            let lines = response.data.split(/\\n/);
            let \$diffPane = \$('#diff')
                .removeClass('loading')
                .addClass('highlight');
            \$diffPane.find('.spinner').remove();
            let \$pre = \$('<pre/>', {
                class: 'language-diff',
            }).appendTo(\$diffPane);
            let \$code = \$('<code/>', {
                class: 'language-diff diff-highlight',
                html: Prism.highlight(lines.slice(0, maxLines).join(\"\\n\"), Prism.languages.diff, 'diff'),
            }).appendTo(\$pre);

            if (lines.length > maxLines) {
                let \$p = \$('<p/>', {
                    class: 'centeralign',
                }).appendTo(\$diffPane);
                let \$a = \$('<a/>', {
                    class: 'largetext',
                    text: Craft.t('app', 'Show all changes'),
                }).appendTo(\$p);
                \$a.on('click', () => {
                    \$code.html(Prism.highlight(response.data, Prism.languages.diff, 'diff'));
                    \$p.remove();
                });
            }
        });
        Garnish.\$win.on('beforeunload', () => {
            if (cancelToken) {
                cancelToken.cancel();
            }
        });
    ";
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
            // line 207
            yield '    ';
            ob_start();
            // line 208
            yield '        #diff.loading {
            height: 200px;
        }
    ';
            craft\helpers\Template::css(ob_get_clean());
        }
        // line 213
        yield '
';
        // line 214
        ob_start();
        // line 215
        yield "    \$('#download').on('click', () => {
        \$('#download-spinner').removeClass('hidden');
        let params = {};
        if (Craft.csrfTokenName) {
            params[Craft.csrfTokenName] = Craft.csrfTokenValue;
        }
        Craft.downloadFromUrl(\"GET\", \"";
        // line 221
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::actionUrl('project-config/download'), 'html', null, true);
        yield "\", params)
            .then(() => {
                \$('#download-spinner').addClass('hidden');
            })
            .catch(e => {
                \$('#download-spinner').addClass('hidden');
                throw e;
            });
    });
";
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        craft\helpers\Template::endProfile('template', '_components/utilities/ProjectConfig.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/ProjectConfig.twig';
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
        return [342 => 221,  334 => 215,  332 => 214,  329 => 213,  322 => 208,  319 => 207,  277 => 168,  273 => 166,  270 => 165,  268 => 164,  265 => 163,  253 => 153,  251 => 152,  244 => 148,  238 => 145,  233 => 143,  228 => 140,  221 => 136,  216 => 134,  212 => 133,  208 => 132,  204 => 131,  199 => 128,  197 => 127,  194 => 126,  187 => 122,  182 => 120,  178 => 119,  174 => 117,  172 => 115,  167 => 113,  164 => 112,  160 => 110,  155 => 107,  153 => 96,  146 => 92,  141 => 89,  137 => 87,  134 => 86,  130 => 84,  127 => 76,  125 => 75,  122 => 74,  120 => 63,  117 => 62,  114 => 52,  110 => 50,  108 => 40,  105 => 39,  102 => 31,  100 => 30,  93 => 25,  91 => 20,  90 => 24,  82 => 14,  80 => 13,  75 => 12,  70 => 10,  65 => 9,  60 => 7,  55 => 6,  53 => 5,  50 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/forms\" as forms %}

{% if yamlExists %}
    <form method=\"post\" action=\"\" accept-charset=\"UTF-8\">
        {% if invert %}
            <h2>{{ 'Update YAML Files'|t('app') }}</h2>
            <p>{{ 'Update your project config YAML files to reflect the latest changes in the loaded project config.'|t('app') }}</p>
        {% else %}
            <h2>{{ 'Apply YAML Changes'|t('app') }}</h2>
            <p>{{ 'Apply changes in your project config YAML files to the loaded project config.'|t('app') }}</p>
        {% endif %}
        {{ csrfInput() }}
        {% if areChangesPending %}
            <div id=\"diff\" class=\"pane loading\" tabindex=\"0\">
                <div class=\"spinner spinner-absolute\"></div>
            </div>
            <div class=\"readable\">
                <blockquote class=\"note \$noteClass\">
                    <p>
                        {{ invert
                            ? 'Make sure you’re not overwriting changes in the YAML files that were made on another environment.'|t('app')
                            : 'Make sure you’ve followed the <a href=\"{url}\" target=\"_blank\">Environment Setup</a> instructions before applying project config YAML changes.'|t('app', {
                                url: 'https://craftcms.com/docs/5.x/system/project-config.html#environment-setup',
                            })|raw
                        }}
                    </p>
                </blockquote>
            </div>
            <div class=\"buttons\">
                {% if invert %}
                    {{ tag('button', {
                        type: 'button',
                        class: ['btn', 'formsubmit', 'secondary'],
                        text: 'Update YAML files'|t('app'),
                        data: {
                            action: 'project-config/discard',
                            confirm: 'Are you sure you want to discard the pending project config YAML changes?'|t('app'),
                        },
                    }) }}
                    {{ tag('button', {
                        type: 'button',
                        class: ['btn', 'formsubmit'],
                        text: 'Apply YAML changes'|t('app'),
                        data: {
                            action: 'config-sync',
                            params: {
                                return: 'utilities/project-config',
                            },
                        },
                    }) }}
                {% else %}
                    {{ tag('button', {
                        type: 'button',
                        class: ['btn', 'formsubmit', 'secondary'],
                        text: 'Apply changes only'|t('app'),
                        data: {
                            action: 'config-sync',
                            params: {
                                return: 'utilities/project-config',
                            },
                        },
                    }) }}
                    {{ tag('button', {
                        type: 'button',
                        class: ['btn', 'formsubmit'],
                        text: 'Reapply everything'|t('app'),
                        data: {
                            action: 'config-sync',
                            params: {
                                return: 'utilities/project-config',
                                force: 1,
                            },
                        },
                    }) }}
                    {% if not readOnly %}
                        {{ tag('button', {
                            type: 'button',
                            class: ['btn', 'formsubmit'],
                            text: 'Discard changes'|t('app'),
                            data: {
                                action: 'project-config/discard',
                                confirm: 'Are you sure you want to discard the pending project config YAML changes?'|t('app'),
                            },
                        }) }}
                    {% endif %}
                {% endif %}
            </div>
        {% else %}
            <div class=\"pane\">
                <p>
                    <span class=\"checkmark-icon\"></span>
                    {{ 'There aren’t any pending project config changes to apply.'|t('app') }}
                </p>
            </div>
            <div class=\"buttons\">
                {{ tag('button', {
                    type: 'button',
                    class: ['btn', 'formsubmit'],
                    text: 'Reapply everything'|t('app'),
                    data: {
                        action: 'config-sync',
                        params: {
                            return: 'utilities/project-config',
                            force: 1,
                        },
                    },
                }) }}
            </div>
        {% endif %}
    </form>
{% else %}
    <form method=\"post\" action=\"\" accept-charset=\"UTF-8\">
        <h2>{{ 'Generate YAML Files'|t('app') }}</h2>
        <p>
            {{ 'Save the loaded project config data to YAML files in your <code>{folder}</code> folder.'|t('app', {
                folder: 'config/project/',
            })|raw }}
        </p>
        {{ actionInput('project-config/discard') }}
        {{ csrfInput() }}
        <div class=\"buttons\">
            <button type=\"submit\" class=\"btn secondary\">{{ 'Generate'|t('app') }}</button>
        </div>
    </form>
{% endif %}

{% if not readOnly %}
    <hr>

    <form method=\"post\" action=\"\" accept-charset=\"UTF-8\">
        <h2>{{ 'Rebuild the Config'|t('app') }}</h2>
        <p>{{ 'Rebuild the project config based on the data stored throughout the database.'|t('app') }}</p>
        {{ actionInput('project-config/rebuild') }}
        {{ csrfInput() }}
        <div class=\"buttons\">
            <button type=\"submit\" class=\"btn secondary\">{{ 'Rebuild'|t('app') }}</button>
        </div>
    </form>
{% endif %}

<hr>

<h2>{{ 'Loaded Project Config Data'|t('app') }}</h2>
<div class=\"pane\" tabindex=\"0\">
    <pre><code>{{ entireConfig }}</code></pre>
</div>
<div class=\"buttons\">
    <button type=\"button\" id=\"download\" class=\"btn\" data-icon=\"download\">{{ 'Download'|t('app') }}</button>
    <div id=\"download-spinner\" class=\"spinner hidden\"></div>
</div>

{% css %}
    .pane {
        max-height: 500px;
        overflow: auto;
    }
    .pane pre {
        margin: 0;
        padding: 0;
        background-color: transparent;
    }
{% endcss %}

{% if areChangesPending %}
    {% js %}
        let cancelToken = axios.CancelToken.source();
        Craft.sendActionRequest('GET', Craft.getActionUrl('project-config/diff', {
            invert: {{ invert|json_encode }},
        }), {
            cancelToken: cancelToken.token,
        }).then(response => {
            cancelToken = null;
            let maxLines = 20;
            let lines = response.data.split(/\\n/);
            let \$diffPane = \$('#diff')
                .removeClass('loading')
                .addClass('highlight');
            \$diffPane.find('.spinner').remove();
            let \$pre = \$('<pre/>', {
                class: 'language-diff',
            }).appendTo(\$diffPane);
            let \$code = \$('<code/>', {
                class: 'language-diff diff-highlight',
                html: Prism.highlight(lines.slice(0, maxLines).join(\"\\n\"), Prism.languages.diff, 'diff'),
            }).appendTo(\$pre);

            if (lines.length > maxLines) {
                let \$p = \$('<p/>', {
                    class: 'centeralign',
                }).appendTo(\$diffPane);
                let \$a = \$('<a/>', {
                    class: 'largetext',
                    text: Craft.t('app', 'Show all changes'),
                }).appendTo(\$p);
                \$a.on('click', () => {
                    \$code.html(Prism.highlight(response.data, Prism.languages.diff, 'diff'));
                    \$p.remove();
                });
            }
        });
        Garnish.\$win.on('beforeunload', () => {
            if (cancelToken) {
                cancelToken.cancel();
            }
        });
    {% endjs %}
    {% css %}
        #diff.loading {
            height: 200px;
        }
    {% endcss %}
{% endif %}

{% js %}
    \$('#download').on('click', () => {
        \$('#download-spinner').removeClass('hidden');
        let params = {};
        if (Craft.csrfTokenName) {
            params[Craft.csrfTokenName] = Craft.csrfTokenValue;
        }
        Craft.downloadFromUrl(\"GET\", \"{{ actionUrl('project-config/download') }}\", params)
            .then(() => {
                \$('#download-spinner').addClass('hidden');
            })
            .catch(e => {
                \$('#download-spinner').addClass('hidden');
                throw e;
            });
    });
{% endjs %}
", '_components/utilities/ProjectConfig.twig', '/tmp/packages/craft5/src/templates/_components/utilities/ProjectConfig.twig');
    }
}
