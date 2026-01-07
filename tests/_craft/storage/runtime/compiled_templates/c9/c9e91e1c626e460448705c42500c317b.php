<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/utilities/ProjectConfig.twig */
class __TwigTemplate_2519a3850b06227d84bee254a8c13831 extends Template
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
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/utilities/ProjectConfig.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/utilities/ProjectConfig.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 3
        if ((isset($context['yamlExists']) || array_key_exists('yamlExists', $context) ? $context['yamlExists'] : (function () {
            throw new RuntimeError('Variable "yamlExists" does not exist.', 3, $this->source);
        })())) {
            // line 4
            echo '    <form method="post" action="" accept-charset="UTF-8">
        ';
            // line 5
            if ((isset($context['invert']) || array_key_exists('invert', $context) ? $context['invert'] : (function () {
                throw new RuntimeError('Variable "invert" does not exist.', 5, $this->source);
            })())) {
                // line 6
                echo '            <h2>';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Update YAML Files', 'app'), 'html', null, true);
                echo '</h2>
            <p>';
                // line 7
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Update your project config YAML files to reflect the latest changes in the loaded project config.', 'app'), 'html', null, true);
                echo '</p>
        ';
            } else {
                // line 9
                echo '            <h2>';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Apply YAML Changes', 'app'), 'html', null, true);
                echo '</h2>
            <p>';
                // line 10
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Apply changes in your project config YAML files to the loaded project config.', 'app'), 'html', null, true);
                echo '</p>
        ';
            }
            // line 12
            echo '        ';
            echo craft\helpers\Html::csrfInput();
            echo '
        ';
            // line 13
            if ((isset($context['areChangesPending']) || array_key_exists('areChangesPending', $context) ? $context['areChangesPending'] : (function () {
                throw new RuntimeError('Variable "areChangesPending" does not exist.', 13, $this->source);
            })())) {
                // line 14
                echo '            <div id="diff" class="pane loading" tabindex="0">
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
                    })())) ? (print twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Make sure you’re not overwriting changes in the YAML files that were made on another environment.', 'app'), 'html', null, true)) : (print $this->extensions['craft\web\twig\Extension']->translateFilter('Make sure you’ve followed the <a href="{url}" target="_blank">Environment Setup</a> instructions before applying project config YAML changes.', 'app', ['url' => 'https://craftcms.com/docs/4.x/project-config.html#environment-setup'])));
                // line 25
                echo '
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
                    echo '                    ';
                    echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => [0 => 'btn', 1 => 'formsubmit', 2 => 'secondary'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Update YAML files', 'app'), 'data' => ['action' => 'project-config/discard', 'confirm' => $this->extensions['craft\web\twig\Extension']->translateFilter('Are you sure you want to discard the pending project config YAML changes?', 'app')]]);
                    // line 39
                    echo '
                    ';
                    // line 40
                    echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => [0 => 'btn', 1 => 'formsubmit'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Apply YAML changes', 'app'), 'data' => ['action' => 'config-sync', 'params' => ['return' => 'utilities/project-config']]]);
                    // line 50
                    echo '
                ';
                } else {
                    // line 52
                    echo '                    ';
                    echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => [0 => 'btn', 1 => 'formsubmit', 2 => 'secondary'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Apply changes only', 'app'), 'data' => ['action' => 'config-sync', 'params' => ['return' => 'utilities/project-config']]]);
                    // line 62
                    echo '
                    ';
                    // line 63
                    echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => [0 => 'btn', 1 => 'formsubmit'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Reapply everything', 'app'), 'data' => ['action' => 'config-sync', 'params' => ['return' => 'utilities/project-config', 'force' => 1]]]);
                    // line 74
                    echo '
                    ';
                    // line 75
                    if (! (isset($context['readOnly']) || array_key_exists('readOnly', $context) ? $context['readOnly'] : (function () {
                        throw new RuntimeError('Variable "readOnly" does not exist.', 75, $this->source);
                    })())) {
                        // line 76
                        echo '                        ';
                        echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => [0 => 'btn', 1 => 'formsubmit'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Discard changes', 'app'), 'data' => ['action' => 'project-config/discard', 'confirm' => $this->extensions['craft\web\twig\Extension']->translateFilter('Are you sure you want to discard the pending project config YAML changes?', 'app')]]);
                        // line 84
                        echo '
                    ';
                    }
                    // line 86
                    echo '                ';
                }
                // line 87
                echo '            </div>
        ';
            } else {
                // line 89
                echo '            <div class="pane">
                <p>
                    <span class="checkmark-icon"></span>
                    ';
                // line 92
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('There aren’t any pending project config changes to apply.', 'app'), 'html', null, true);
                echo '
                </p>
            </div>
            <div class="buttons">
                ';
                // line 96
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['type' => 'button', 'class' => [0 => 'btn', 1 => 'formsubmit'], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Reapply everything', 'app'), 'data' => ['action' => 'config-sync', 'params' => ['return' => 'utilities/project-config', 'force' => 1]]]);
                // line 107
                echo '
            </div>
        ';
            }
            // line 110
            echo '    </form>
';
        } else {
            // line 112
            echo '    <form method="post" action="" accept-charset="UTF-8">
        <h2>';
            // line 113
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Generate YAML Files', 'app'), 'html', null, true);
            echo '</h2>
        <p>
            ';
            // line 115
            echo $this->extensions['craft\web\twig\Extension']->translateFilter('Save the loaded project config data to YAML files in your <code>{folder}</code> folder.', 'app', ['folder' => 'config/project/']);
            // line 117
            echo '
        </p>
        ';
            // line 119
            echo craft\helpers\Html::actionInput('project-config/discard');
            echo '
        ';
            // line 120
            echo craft\helpers\Html::csrfInput();
            echo '
        <div class="buttons">
            <button type="submit" class="btn secondary">';
            // line 122
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Generate', 'app'), 'html', null, true);
            echo '</button>
        </div>
    </form>
';
        }
        // line 126
        echo '
';
        // line 127
        if (! (isset($context['readOnly']) || array_key_exists('readOnly', $context) ? $context['readOnly'] : (function () {
            throw new RuntimeError('Variable "readOnly" does not exist.', 127, $this->source);
        })())) {
            // line 128
            echo '    <hr>

    <form method="post" action="" accept-charset="UTF-8">
        <h2>';
            // line 131
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Rebuild the Config', 'app'), 'html', null, true);
            echo '</h2>
        <p>';
            // line 132
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Rebuild the project config based on the data stored throughout the database.', 'app'), 'html', null, true);
            echo '</p>
        ';
            // line 133
            echo craft\helpers\Html::actionInput('project-config/rebuild');
            echo '
        ';
            // line 134
            echo craft\helpers\Html::csrfInput();
            echo '
        <div class="buttons">
            <button type="submit" class="btn secondary">';
            // line 136
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Rebuild', 'app'), 'html', null, true);
            echo '</button>
        </div>
    </form>
';
        }
        // line 140
        echo '
<hr>

<h2>';
        // line 143
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Loaded Project Config Data', 'app'), 'html', null, true);
        echo '</h2>
<div class="pane" tabindex="0">
    <pre><code>';
        // line 145
        echo twig_escape_filter($this->env, (isset($context['entireConfig']) || array_key_exists('entireConfig', $context) ? $context['entireConfig'] : (function () {
            throw new RuntimeError('Variable "entireConfig" does not exist.', 145, $this->source);
        })()), 'html', null, true);
        echo '</code></pre>
</div>
<div class="buttons">
    <button type="button" id="download" class="btn" data-icon="download">';
        // line 148
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Download', 'app'), 'html', null, true);
        echo '</button>
    <div id="download-spinner" class="spinner hidden"></div>
</div>

';
        // line 152
        ob_start();
        // line 153
        echo '    .pane {
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
        echo '
';
        // line 164
        if ((isset($context['areChangesPending']) || array_key_exists('areChangesPending', $context) ? $context['areChangesPending'] : (function () {
            throw new RuntimeError('Variable "areChangesPending" does not exist.', 164, $this->source);
        })())) {
            // line 165
            echo '    ';
            ob_start();
            // line 166
            echo "        let cancelToken = axios.CancelToken.source();
        Craft.sendActionRequest('GET', Craft.getActionUrl('project-config/diff', {
            invert: ";
            // line 168
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['invert']) || array_key_exists('invert', $context) ? $context['invert'] : (function () {
                throw new RuntimeError('Variable "invert" does not exist.', 168, $this->source);
            })())), 'html', null, true);
            echo ",
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
            echo '    ';
            ob_start();
            // line 208
            echo '        #diff.loading {
            height: 200px;
        }
    ';
            craft\helpers\Template::css(ob_get_clean());
        }
        // line 213
        echo '
';
        // line 214
        ob_start();
        // line 215
        echo "    \$('#download').on('click', () => {
        \$('#download-spinner').removeClass('hidden');
        let params = {};
        if (Craft.csrfTokenName) {
            params[Craft.csrfTokenName] = Craft.csrfTokenValue;
        }
        Craft.downloadFromUrl(\"GET\", \"";
        // line 221
        echo twig_escape_filter($this->env, craft\helpers\UrlHelper::actionUrl('project-config/download'), 'html', null, true);
        echo "\", params)
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
    }

    public function getTemplateName()
    {
        return '_components/utilities/ProjectConfig.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [337 => 221,  329 => 215,  327 => 214,  324 => 213,  317 => 208,  314 => 207,  272 => 168,  268 => 166,  265 => 165,  263 => 164,  260 => 163,  248 => 153,  246 => 152,  239 => 148,  233 => 145,  228 => 143,  223 => 140,  216 => 136,  211 => 134,  207 => 133,  203 => 132,  199 => 131,  194 => 128,  192 => 127,  189 => 126,  182 => 122,  177 => 120,  173 => 119,  169 => 117,  167 => 115,  162 => 113,  159 => 112,  155 => 110,  150 => 107,  148 => 96,  141 => 92,  136 => 89,  132 => 87,  129 => 86,  125 => 84,  122 => 76,  120 => 75,  117 => 74,  115 => 63,  112 => 62,  109 => 52,  105 => 50,  103 => 40,  100 => 39,  97 => 31,  95 => 30,  88 => 25,  86 => 20,  85 => 24,  77 => 14,  75 => 13,  70 => 12,  65 => 10,  60 => 9,  55 => 7,  50 => 6,  48 => 5,  45 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
                                url: 'https://craftcms.com/docs/4.x/project-config.html#environment-setup',
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
", '_components/utilities/ProjectConfig.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/ProjectConfig.twig');
    }
}
