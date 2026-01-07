<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms */
class __TwigTemplate_f77dfa6e78f197d7c1e45d48bdae9716 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms');
        // line 4
        echo '

';
        // line 7
        echo '

';
        // line 12
        echo '

';
        // line 21
        echo '

';
        // line 24
        echo '

';
        // line 29
        echo '

';
        // line 34
        echo '

';
        // line 39
        echo '

';
        // line 44
        echo '

';
        // line 49
        echo '

';
        // line 54
        echo '

';
        // line 59
        echo '

';
        // line 64
        echo '

';
        // line 69
        echo '

';
        // line 74
        echo '

';
        // line 79
        echo '

';
        // line 84
        echo '

';
        // line 89
        echo '

';
        // line 94
        echo '

';
        // line 99
        echo '

';
        // line 104
        echo '

';
        // line 109
        echo '

';
        // line 114
        echo '

';
        // line 119
        echo '

';
        // line 124
        echo '

';
        // line 129
        echo '

';
        // line 134
        echo '

';
        // line 139
        echo '

';
        // line 144
        echo '

';
        // line 149
        echo '

';
        // line 154
        echo '

';
        // line 159
        echo '

';
        // line 164
        echo '

';
        // line 169
        echo '

';
        // line 174
        echo '

';
        // line 179
        echo '

';
        // line 184
        echo '

';
        // line 189
        echo '

';
        // line 192
        echo '

';
        // line 197
        echo '

';
        // line 203
        echo '

';
        // line 209
        echo '

';
        // line 215
        echo '

';
        // line 221
        echo '

';
        // line 227
        echo '

';
        // line 236
        echo '

';
        // line 242
        echo '

';
        // line 251
        echo '

';
        // line 257
        echo '

';
        // line 263
        echo '

';
        // line 269
        echo '

';
        // line 282
        echo '

';
        // line 288
        echo '

';
        // line 301
        echo '

';
        // line 310
        echo '

';
        // line 319
        echo '

';
        // line 328
        echo '

';
        // line 334
        echo '

';
        // line 344
        echo '

';
        // line 350
        echo '

';
        // line 356
        echo '

';
        // line 362
        echo '

';
        // line 368
        echo '

';
        // line 397
        echo '

';
        // line 410
        echo '

';
        // line 416
        echo '

';
        // line 427
        echo '

';
        // line 438
        echo '

';
        // line 451
        echo '

';
        // line 464
        echo '

';
        // line 472
        echo '

';
        // line 478
        echo '

';
        // line 481
        echo '

';
        // line 486
        echo '
';
        craft\helpers\Template::endProfile('template', '_includes/forms');
    }

    // line 1
    public function macro_errorList($__errors__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'errors' => $__errors__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'errorList');
            // line 2
            echo '    ';
            $this->loadTemplate('_includes/forms/errorList', '_includes/forms', 2)->display($context);
            craft\helpers\Template::endProfile('macro', 'errorList');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 9
    public function macro_button($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'button');
            // line 10
            echo '    ';
            $this->loadTemplate('_includes/forms/button', '_includes/forms', 10)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 10, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'button');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 14
    public function macro_submitButton($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'submitButton');
            // line 15
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_button', [$this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 15, $this->source);
            })()), ['type' => 'submit', 'class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((((craft\helpers\Template::attribute($this->env, $this->source,             // line 17
                ($context['config'] ?? null), 'class', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'class', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'class', [])) : ([]))), [0 => 'submit']), 'label' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 18
                    ($context['config'] ?? null), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [])) : ($this->extensions['craft\web\twig\Extension']->translateFilter('Submit', 'app')))])], 15, $context, $this->getSourceContext());
            // line 19
            echo '
';
            craft\helpers\Template::endProfile('macro', 'submitButton');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 26
    public function macro_hidden($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'hidden');
            // line 27
            $this->loadTemplate('_includes/forms/hidden', '_includes/forms', 27)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 27, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'hidden');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 31
    public function macro_text($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'text');
            // line 32
            echo '    ';
            $this->loadTemplate('_includes/forms/text', '_includes/forms', 32)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 32, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'text');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 36
    public function macro_password($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'password');
            // line 37
            echo '    ';
            $this->loadTemplate('_includes/forms/password', '_includes/forms', 37)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 37, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'password');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 41
    public function macro_copytext($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'copytext');
            // line 42
            echo '    ';
            $this->loadTemplate('_includes/forms/copytext', '_includes/forms', 42)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 42, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'copytext');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 46
    public function macro_date($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'date');
            // line 47
            echo '    ';
            $this->loadTemplate('_includes/forms/date', '_includes/forms', 47)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 47, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'date');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 51
    public function macro_time($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'time');
            // line 52
            echo '    ';
            $this->loadTemplate('_includes/forms/time', '_includes/forms', 52)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 52, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'time');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 56
    public function macro_color($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'color');
            // line 57
            echo '    ';
            $this->loadTemplate('_includes/forms/color', '_includes/forms', 57)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 57, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'color');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 61
    public function macro_colorSelect($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'colorSelect');
            // line 62
            echo '    ';
            $this->loadTemplate('_includes/forms/colorSelect', '_includes/forms', 62)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 62, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'colorSelect');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 66
    public function macro_textarea($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'textarea');
            // line 67
            echo '    ';
            $this->loadTemplate('_includes/forms/textarea', '_includes/forms', 67)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 67, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'textarea');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 71
    public function macro_select($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'select');
            // line 72
            echo '    ';
            $this->loadTemplate('_includes/forms/select', '_includes/forms', 72)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 72, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'select');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 76
    public function macro_customSelect($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'customSelect');
            // line 77
            echo '    ';
            $this->loadTemplate('_includes/forms/customSelect', '_includes/forms', 77)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 77, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'customSelect');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 81
    public function macro_selectize($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'selectize');
            // line 82
            echo '    ';
            $this->loadTemplate('_includes/forms/selectize', '_includes/forms', 82)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 82, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'selectize');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 86
    public function macro_multiselect($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'multiselect');
            // line 87
            echo '    ';
            $this->loadTemplate('_includes/forms/multiselect', '_includes/forms', 87)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 87, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'multiselect');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 91
    public function macro_checkbox($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'checkbox');
            // line 92
            echo '    ';
            $this->loadTemplate('_includes/forms/checkbox', '_includes/forms', 92)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 92, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'checkbox');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 96
    public function macro_checkboxGroup($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'checkboxGroup');
            // line 97
            echo '    ';
            $this->loadTemplate('_includes/forms/checkboxGroup', '_includes/forms', 97)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 97, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'checkboxGroup');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 101
    public function macro_checkboxSelect($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'checkboxSelect');
            // line 102
            echo '    ';
            $this->loadTemplate('_includes/forms/checkboxSelect', '_includes/forms', 102)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 102, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'checkboxSelect');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 106
    public function macro_radio($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'radio');
            // line 107
            echo '    ';
            $this->loadTemplate('_includes/forms/radio', '_includes/forms', 107)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 107, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'radio');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 111
    public function macro_radioGroup($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'radioGroup');
            // line 112
            echo '    ';
            $this->loadTemplate('_includes/forms/radioGroup', '_includes/forms', 112)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 112, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'radioGroup');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 116
    public function macro_file($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'file');
            // line 117
            echo '    ';
            $this->loadTemplate('_includes/forms/file', '_includes/forms', 117)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 117, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'file');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 121
    public function macro_lightswitch($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'lightswitch');
            // line 122
            echo '    ';
            $this->loadTemplate('_includes/forms/lightswitch', '_includes/forms', 122)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 122, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'lightswitch');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 126
    public function macro_editableTable($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'editableTable');
            // line 127
            echo '    ';
            $this->loadTemplate('_includes/forms/editableTable', '_includes/forms', 127)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 127, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'editableTable');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 131
    public function macro_elementSelect($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'elementSelect');
            // line 132
            echo '    ';
            $this->loadTemplate('_includes/forms/elementSelect', '_includes/forms', 132)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 132, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'elementSelect');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 136
    public function macro_componentSelect($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'componentSelect');
            // line 137
            echo '    ';
            $this->loadTemplate('_includes/forms/componentSelect', '_includes/forms', 137)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 137, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'componentSelect');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 141
    public function macro_entryTypeSelect($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'entryTypeSelect');
            // line 142
            echo '    ';
            $this->loadTemplate('_includes/forms/entryTypeSelect', '_includes/forms', 142)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 142, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'entryTypeSelect');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 146
    public function macro_autosuggest($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'autosuggest');
            // line 147
            echo '    ';
            $this->loadTemplate('_includes/forms/autosuggest', '_includes/forms', 147)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 147, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'autosuggest');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 151
    public function macro_timeZone($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'timeZone');
            // line 152
            echo '    ';
            $this->loadTemplate('_includes/forms/timeZone', '_includes/forms', 152)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 152, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'timeZone');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 156
    public function macro_iconPicker($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'iconPicker');
            // line 157
            echo '    ';
            $this->loadTemplate('_includes/forms/iconPicker', '_includes/forms', 157)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 157, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'iconPicker');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 161
    public function macro_fs($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'fs');
            // line 162
            echo '    ';
            $this->loadTemplate('_includes/forms/fs', '_includes/forms', 162)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 162, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'fs');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 166
    public function macro_volume($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'volume');
            // line 167
            echo '    ';
            $this->loadTemplate('_includes/forms/volume', '_includes/forms', 167)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 167, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'volume');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 171
    public function macro_booleanMenu($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'booleanMenu');
            // line 172
            echo '    ';
            $this->loadTemplate('_includes/forms/booleanMenu', '_includes/forms', 172)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 172, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'booleanMenu');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 176
    public function macro_languageMenu($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'languageMenu');
            // line 177
            echo '    ';
            $this->loadTemplate('_includes/forms/languageMenu', '_includes/forms', 177)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 177, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'languageMenu');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 181
    public function macro_fieldLayoutDesigner($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'fieldLayoutDesigner');
            // line 182
            echo '    ';
            $this->loadTemplate('_includes/forms/fieldLayoutDesigner', '_includes/forms', 182)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 182, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'fieldLayoutDesigner');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 186
    public function macro_money($__config__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'money');
            // line 187
            echo '    ';
            $this->loadTemplate('_includes/forms/money', '_includes/forms', 187)->display(twig_to_array((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 187, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'money');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 194
    public function macro_field($__config__ = null, $__input__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'input' => $__input__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'field');
            // line 195
            echo '    ';
            echo craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 195, $this->source);
            })()), 'cp', []), 'field', [0 => (($context['input']) ?? ('')), 1 => (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 195, $this->source);
            })())], 'method');
            echo '
';
            craft\helpers\Template::endProfile('macro', 'field');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 199
    public function macro_textField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'textField');
            // line 200
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 200, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('text'.twig_random($this->env))))]);
            // line 201
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 201, $this->source);
            })()), 'template:_includes/forms/text'], 201, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'textField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 205
    public function macro_copytextField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'copytextField');
            // line 206
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 206, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('copytext'.twig_random($this->env))))]);
            // line 207
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 207, $this->source);
            })()), 'template:_includes/forms/copytext'], 207, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'copytextField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 211
    public function macro_passwordField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'passwordField');
            // line 212
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 212, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('password'.twig_random($this->env))))]);
            // line 213
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 213, $this->source);
            })()), 'template:_includes/forms/password'], 213, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'passwordField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 217
    public function macro_dateField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'dateField');
            // line 218
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 218, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('date'.twig_random($this->env))))]);
            // line 219
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 219, $this->source);
            })()), 'template:_includes/forms/date'], 219, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'dateField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 223
    public function macro_timeField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'timeField');
            // line 224
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 224, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('time'.twig_random($this->env))))]);
            // line 225
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 225, $this->source);
            })()), 'template:_includes/forms/time'], 225, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'timeField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 229
    public function macro_colorField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'colorField');
            // line 230
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 230, $this->source);
            })()), ['fieldset' => true, 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 232
                ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('color'.twig_random($this->env))))]);
            // line 234
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 234, $this->source);
            })()), 'template:_includes/forms/color'], 234, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'colorField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 238
    public function macro_colorSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'colorSelectField');
            // line 239
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 239, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('colorselect'.twig_random($this->env))))]);
            // line 240
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 240, $this->source);
            })()), 'template:_includes/forms/colorSelect'], 240, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'colorSelectField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 244
    public function macro_dateTimeField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'dateTimeField');
            // line 245
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 245, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 246
                ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('datetime'.twig_random($this->env)))), 'fieldset' => true]);
            // line 249
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 249, $this->source);
            })()), 'template:_includes/forms/datetime'], 249, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'dateTimeField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 253
    public function macro_textareaField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'textareaField');
            // line 254
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 254, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('textarea'.twig_random($this->env))))]);
            // line 255
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 255, $this->source);
            })()), 'template:_includes/forms/textarea'], 255, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'textareaField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 259
    public function macro_selectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'selectField');
            // line 260
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 260, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('select'.twig_random($this->env))))]);
            // line 261
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 261, $this->source);
            })()), 'template:_includes/forms/select'], 261, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'selectField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 265
    public function macro_customSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'customSelectField');
            // line 266
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 266, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('customselect'.twig_random($this->env))))]);
            // line 267
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 267, $this->source);
            })()), 'template:_includes/forms/customSelect'], 267, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'customSelectField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 271
    public function macro_selectizeField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'selectizeField');
            // line 272
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 272, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('selectize'.twig_random($this->env))))]);
            // line 273
            echo '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [])) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true)) && (twig_slice($this->env, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [])) : ('')), 0, 1) != '$'))) {
                // line 274
                echo '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 274, $this->source);
                })()), ['tip' => ((! craft\helpers\Template::attribute($this->env, $this->source,                 // line 275
                    ($context['config'] ?? null), 'allowedEnvValues', [], 'any', true, true)) ? ($this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable matching one of the option values.', 'app')) : ($this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable.', 'app')))]);
                // line 279
                echo '    ';
            }
            // line 280
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 280, $this->source);
            })()), 'template:_includes/forms/selectize'], 280, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'selectizeField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 284
    public function macro_multiselectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'multiselectField');
            // line 285
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 285, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('multiselect'.twig_random($this->env))))]);
            // line 286
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 286, $this->source);
            })()), 'template:_includes/forms/multiselect'], 286, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'multiselectField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 290
    public function macro_checkboxField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'checkboxField');
            // line 291
            echo '    ';
            // line 292
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->withoutKeyFilter($this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 292, $this->source);
            })()), ['fieldset' => craft\helpers\Template::attribute($this->env, $this->source,             // line 293
                ($context['config'] ?? null), 'fieldLabel', [], 'any', true, true), 'fieldClass' => $this->extensions['craft\web\twig\Extension']->pushFilter(craft\helpers\Html::explodeClass((((craft\helpers\Template::attribute($this->env, $this->source,             // line 294
                    ($context['config'] ?? null), 'fieldClass', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldClass', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldClass', [])) : ([]))), 'checkboxfield'), 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 295
                        ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('checkbox'.twig_random($this->env)))), 'checkboxLabel' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 296
                            ($context['config'] ?? null), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [])) : (null)), 'instructionsPosition' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 297
                                ($context['config'] ?? null), 'instructionsPosition', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'instructionsPosition', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'instructionsPosition', [])) : ('after'))]), 'label');
            // line 299
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 299, $this->source);
            })()), 'template:_includes/forms/checkbox'], 299, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'checkboxField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 303
    public function macro_checkboxGroupField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'checkboxGroupField');
            // line 304
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 304, $this->source);
            })()), ['fieldset' => true, 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 306
                ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('checkboxgroup'.twig_random($this->env))))]);
            // line 308
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 308, $this->source);
            })()), 'template:_includes/forms/checkboxGroup'], 308, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'checkboxGroupField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 312
    public function macro_checkboxSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'checkboxSelectField');
            // line 313
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 313, $this->source);
            })()), ['fieldset' => true, 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 315
                ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('checkboxselect'.twig_random($this->env))))]);
            // line 317
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 317, $this->source);
            })()), 'template:_includes/forms/checkboxSelect'], 317, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'checkboxSelectField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 321
    public function macro_radioGroupField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'radioGroupField');
            // line 322
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 322, $this->source);
            })()), ['fieldset' => true, 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 324
                ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('radiogroup'.twig_random($this->env))))]);
            // line 326
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 326, $this->source);
            })()), 'template:_includes/forms/radioGroup'], 326, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'radioGroupField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 330
    public function macro_fileField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'fileField');
            // line 331
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 331, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('file'.twig_random($this->env))))]);
            // line 332
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 332, $this->source);
            })()), 'template:_includes/forms/file'], 332, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'fileField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 336
    public function macro_lightswitchField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'lightswitchField');
            // line 337
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->withoutKeyFilter($this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 337, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 338
                ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('lightswitch'.twig_random($this->env)))), 'fieldClass' => $this->extensions['craft\web\twig\Extension']->pushFilter(craft\helpers\Html::explodeClass((((craft\helpers\Template::attribute($this->env, $this->source,             // line 339
                    ($context['config'] ?? null), 'fieldClass', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldClass', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldClass', [])) : ([]))), 'lightswitch-field'), 'fieldLabel' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 340
                        ($context['config'] ?? null), 'fieldLabel', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldLabel', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldLabel', [])) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [])) : (null))))]), 'label');
            // line 342
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 342, $this->source);
            })()), 'template:_includes/forms/lightswitch'], 342, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'lightswitchField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 346
    public function macro_editableTableField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'editableTableField');
            // line 347
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 347, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('editabletable'.twig_random($this->env))))]);
            // line 348
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 348, $this->source);
            })()), 'template:_includes/forms/editableTable'], 348, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'editableTableField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 352
    public function macro_elementSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'elementSelectField');
            // line 353
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 353, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('elementselect'.twig_random($this->env))))]);
            // line 354
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 354, $this->source);
            })()), 'template:_includes/forms/elementSelect'], 354, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'elementSelectField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 358
    public function macro_componentSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'componentSelectField');
            // line 359
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 359, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('componentselect'.twig_random($this->env))))]);
            // line 360
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 360, $this->source);
            })()), 'template:_includes/forms/componentSelect'], 360, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'componentSelectField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 364
    public function macro_entryTypeSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'entryTypeSelectField');
            // line 365
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 365, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('entrytypeselect'.twig_random($this->env))))]);
            // line 366
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 366, $this->source);
            })()), 'template:_includes/forms/entryTypeSelect'], 366, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'entryTypeSelectField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 370
    public function macro_autosuggestField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'autosuggestField');
            // line 371
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 371, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 372
                ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('autosuggest'.twig_random($this->env))))]);
            // line 374
            echo '
    ';
            // line 376
            echo '    ';
            if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestEnvVars', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestEnvVars', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestEnvVars', [])) : (false))) {
                // line 377
                echo '        ';
                $context['value'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [])) : (''));
                // line 378
                echo '        ';
                if ((! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true) && ! twig_in_filter(twig_slice($this->env, (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                    throw new RuntimeError('Variable "value" does not exist.', 378, $this->source);
                })()), 0, 1), [0 => '$', 1 => '@']))) {
                    // line 379
                    echo '            ';
                    $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                        throw new RuntimeError('Variable "config" does not exist.', 379, $this->source);
                    })()), ['tip' => (((((((craft\helpers\Template::attribute($this->env, $this->source,                     // line 380
                        ($context['config'] ?? null), 'suggestAliases', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestAliases', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestAliases', [])) : (false))) ? ($this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable, or begin with an alias.', 'app')) : ($this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable.', 'app'))).' ').$this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => 'https://craftcms.com/docs/4.x/config/#control-panel-settings', 'class' => 'go', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Learn more', 'app')]))]);
                    // line 388
                    echo '        ';
                } elseif (((! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'warning', [], 'any', true, true) && (((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                    throw new RuntimeError('Variable "value" does not exist.', 388, $this->source);
                })()) == '@web') || (twig_slice($this->env, (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                    throw new RuntimeError('Variable "value" does not exist.', 388, $this->source);
                })()), 0, 5) == '@web/'))) && craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 388, $this->source);
                })()), 'app', []), 'request', []), 'isWebAliasSetDynamically', []))) {
                    // line 389
                    echo '            ';
                    $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                        throw new RuntimeError('Variable "config" does not exist.', 389, $this->source);
                    })()), ['warning' => $this->extensions['craft\web\twig\Extension']->translateFilter('The `@web` alias is not recommended if it is determined automatically.', 'app')]);
                    // line 392
                    echo '        ';
                }
                // line 393
                echo '    ';
            }
            // line 394
            echo '
    ';
            // line 395
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 395, $this->source);
            })()), 'template:_includes/forms/autosuggest'], 395, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'autosuggestField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 399
    public function macro_timeZoneField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'timeZoneField');
            // line 400
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 400, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('timezone'.twig_random($this->env))))]);
            // line 401
            echo '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [])) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true)) && (twig_slice($this->env, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [])) : ('')), 0, 1) != '$'))) {
                // line 402
                echo '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 402, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable with a value of a [supported time zone]({url}).', 'app', ['url' => 'https://www.php.net/manual/en/timezones.php'])]);
                // line 407
                echo '    ';
            }
            // line 408
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 408, $this->source);
            })()), 'template:_includes/forms/timeZone'], 408, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'timeZoneField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 412
    public function macro_iconPickerField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'iconPickerField');
            // line 413
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 413, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('iconpicker'.twig_random($this->env))))]);
            // line 414
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 414, $this->source);
            })()), 'template:_includes/forms/iconPicker'], 414, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'iconPickerField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 418
    public function macro_fsField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'fsField');
            // line 419
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 419, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('fs'.twig_random($this->env))))]);
            // line 420
            echo '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [])) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true)) && (twig_slice($this->env, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [])) : ('')), 0, 1) != '$'))) {
                // line 421
                echo '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 421, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable matching one of the option values.', 'app')]);
                // line 424
                echo '    ';
            }
            // line 425
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 425, $this->source);
            })()), 'template:_includes/forms/fs'], 425, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'fsField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 429
    public function macro_volumeField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'volumeField');
            // line 430
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 430, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('volume'.twig_random($this->env))))]);
            // line 431
            echo '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [])) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true)) && (twig_slice($this->env, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [])) : ('')), 0, 1) != '$'))) {
                // line 432
                echo '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 432, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable matching one of the option values.', 'app')]);
                // line 435
                echo '    ';
            }
            // line 436
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 436, $this->source);
            })()), 'template:_includes/forms/volume'], 436, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'volumeField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 440
    public function macro_booleanMenuField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'booleanMenuField');
            // line 441
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 441, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('booleanmenu'.twig_random($this->env))))]);
            // line 442
            echo '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [])) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true)) && (twig_slice($this->env, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [])) : ('')), 0, 1) != '$'))) {
                // line 443
                echo '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 443, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable with a boolean value ({examples}).', 'app', ['examples' => '`yes`/`no`/`true`/`false`/`on`/`off`/`0`/`1`'])]);
                // line 448
                echo '    ';
            }
            // line 449
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 449, $this->source);
            })()), 'template:_includes/forms/booleanMenu'], 449, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'booleanMenuField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 453
    public function macro_languageMenuField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'languageMenuField');
            // line 454
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 454, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('languagemenu'.twig_random($this->env))))]);
            // line 455
            echo '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [])) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true)) && (twig_slice($this->env, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [])) : ('')), 0, 1) != '$'))) {
                // line 456
                echo '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 456, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable with a valid language ID ({examples}).', 'app', ['examples' => '`en`/`en-GB`'])]);
                // line 461
                echo '    ';
            }
            // line 462
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 462, $this->source);
            })()), 'template:_includes/forms/languageMenu'], 462, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'languageMenuField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 466
    public function macro_fieldLayoutDesignerField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'fieldLayoutDesignerField');
            // line 467
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [$this->extensions['craft\web\twig\Extension']->mergeFilter(['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Field Layout', 'app'), 'errors' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 469
                ($context['config'] ?? null), 'fieldLayout', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldLayout', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldLayout', [])) : (false))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 469, $this->source);
                })()), 'fieldLayout', []), 'getErrorSummary', [0 => true], 'method')) : (''))],             // line 470
                (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 470, $this->source);
                })())), 'template:_includes/forms/fieldLayoutDesigner'], 467, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'fieldLayoutDesignerField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 474
    public function macro_moneyField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'config' => $__config__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'moneyField');
            // line 475
            echo '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 475, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [])) : (('money'.twig_random($this->env))))]);
            // line 476
            echo '    ';
            echo twig_call_macro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 476, $this->source);
            })()), 'template:_includes/forms/money'], 476, $context, $this->getSourceContext());
            echo '
';
            craft\helpers\Template::endProfile('macro', 'moneyField');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 483
    public function macro_optionShortcutLabel($__key__ = null, $__shift__ = null, $__alt__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'key' => $__key__,
            'shift' => $__shift__,
            'alt' => $__alt__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'optionShortcutLabel');
            // line 484
            echo '<span class="shortcut">';
            echo twig_call_macro($macros['_self'], 'macro_shortcutText', [(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                throw new RuntimeError('Variable "key" does not exist.', 484, $this->source);
            })()), (isset($context['shift']) || array_key_exists('shift', $context) ? $context['shift'] : (function () {
                throw new RuntimeError('Variable "shift" does not exist.', 484, $this->source);
            })()), (isset($context['alt']) || array_key_exists('alt', $context) ? $context['alt'] : (function () {
                throw new RuntimeError('Variable "alt" does not exist.', 484, $this->source);
            })())], 484, $context, $this->getSourceContext());
            echo '</span>';
            craft\helpers\Template::endProfile('macro', 'optionShortcutLabel');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 487
    public function macro_shortcutText($__key__ = null, $__shift__ = null, $__alt__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'key' => $__key__,
            'shift' => $__shift__,
            'alt' => $__alt__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'shortcutText');
            // line 488
            switch (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 488, $this->source);
            })()), 'app', []), 'request', []), 'getClientOs', [], 'method')) {
                case 'Mac' :
                    // line 490
                    echo twig_escape_filter($this->env, ((((((isset($context['alt']) || array_key_exists('alt', $context) ? $context['alt'] : (function () {
                        throw new RuntimeError('Variable "alt" does not exist.', 490, $this->source);
                    })())) ? ('⌥') : ('')).(((isset($context['shift']) || array_key_exists('shift', $context) ? $context['shift'] : (function () {
                        throw new RuntimeError('Variable "shift" does not exist.', 490, $this->source);
                    })())) ? ('⇧') : (''))).'⌘').(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                        throw new RuntimeError('Variable "key" does not exist.', 490, $this->source);
                    })())), 'html', null, true);
                    break;
                default :
                    // line 492
                    echo twig_escape_filter($this->env, ((('Ctrl+'.(((isset($context['alt']) || array_key_exists('alt', $context) ? $context['alt'] : (function () {
                        throw new RuntimeError('Variable "alt" does not exist.', 492, $this->source);
                    })())) ? ('Alt+') : (''))).(((isset($context['shift']) || array_key_exists('shift', $context) ? $context['shift'] : (function () {
                        throw new RuntimeError('Variable "shift" does not exist.', 492, $this->source);
                    })())) ? ('Shift+') : (''))).(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                        throw new RuntimeError('Variable "key" does not exist.', 492, $this->source);
                    })())), 'html', null, true);
            }
            craft\helpers\Template::endProfile('macro', 'shortcutText');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_includes/forms';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [2383 => 492,  2377 => 490,  2373 => 488,  2357 => 487,  2345 => 484,  2329 => 483,  2316 => 476,  2313 => 475,  2299 => 474,  2287 => 470,  2286 => 469,  2284 => 467,  2270 => 466,  2257 => 462,  2254 => 461,  2251 => 456,  2248 => 455,  2245 => 454,  2231 => 453,  2218 => 449,  2215 => 448,  2212 => 443,  2209 => 442,  2206 => 441,  2192 => 440,  2179 => 436,  2176 => 435,  2173 => 432,  2170 => 431,  2167 => 430,  2153 => 429,  2140 => 425,  2137 => 424,  2134 => 421,  2131 => 420,  2128 => 419,  2114 => 418,  2101 => 414,  2098 => 413,  2084 => 412,  2071 => 408,  2068 => 407,  2065 => 402,  2062 => 401,  2059 => 400,  2045 => 399,  2033 => 395,  2030 => 394,  2027 => 393,  2024 => 392,  2021 => 389,  2018 => 388,  2016 => 380,  2014 => 379,  2011 => 378,  2008 => 377,  2005 => 376,  2002 => 374,  2000 => 372,  1998 => 371,  1984 => 370,  1971 => 366,  1968 => 365,  1954 => 364,  1941 => 360,  1938 => 359,  1924 => 358,  1911 => 354,  1908 => 353,  1894 => 352,  1881 => 348,  1878 => 347,  1864 => 346,  1851 => 342,  1849 => 340,  1848 => 339,  1847 => 338,  1845 => 337,  1831 => 336,  1818 => 332,  1815 => 331,  1801 => 330,  1788 => 326,  1786 => 324,  1784 => 322,  1770 => 321,  1757 => 317,  1755 => 315,  1753 => 313,  1739 => 312,  1726 => 308,  1724 => 306,  1722 => 304,  1708 => 303,  1695 => 299,  1693 => 297,  1692 => 296,  1691 => 295,  1690 => 294,  1689 => 293,  1687 => 292,  1685 => 291,  1671 => 290,  1658 => 286,  1655 => 285,  1641 => 284,  1628 => 280,  1625 => 279,  1623 => 275,  1621 => 274,  1618 => 273,  1615 => 272,  1601 => 271,  1588 => 267,  1585 => 266,  1571 => 265,  1558 => 261,  1555 => 260,  1541 => 259,  1528 => 255,  1525 => 254,  1511 => 253,  1498 => 249,  1496 => 246,  1494 => 245,  1480 => 244,  1467 => 240,  1464 => 239,  1450 => 238,  1437 => 234,  1435 => 232,  1433 => 230,  1419 => 229,  1406 => 225,  1403 => 224,  1389 => 223,  1376 => 219,  1373 => 218,  1359 => 217,  1346 => 213,  1343 => 212,  1329 => 211,  1316 => 207,  1313 => 206,  1299 => 205,  1286 => 201,  1283 => 200,  1269 => 199,  1256 => 195,  1241 => 194,  1230 => 187,  1216 => 186,  1205 => 182,  1191 => 181,  1180 => 177,  1166 => 176,  1155 => 172,  1141 => 171,  1130 => 167,  1116 => 166,  1105 => 162,  1091 => 161,  1080 => 157,  1066 => 156,  1055 => 152,  1041 => 151,  1030 => 147,  1016 => 146,  1005 => 142,  991 => 141,  980 => 137,  966 => 136,  955 => 132,  941 => 131,  930 => 127,  916 => 126,  905 => 122,  891 => 121,  880 => 117,  866 => 116,  855 => 112,  841 => 111,  830 => 107,  816 => 106,  805 => 102,  791 => 101,  780 => 97,  766 => 96,  755 => 92,  741 => 91,  730 => 87,  716 => 86,  705 => 82,  691 => 81,  680 => 77,  666 => 76,  655 => 72,  641 => 71,  630 => 67,  616 => 66,  605 => 62,  591 => 61,  580 => 57,  566 => 56,  555 => 52,  541 => 51,  530 => 47,  516 => 46,  505 => 42,  491 => 41,  480 => 37,  466 => 36,  455 => 32,  441 => 31,  431 => 27,  417 => 26,  406 => 19,  404 => 18,  403 => 17,  401 => 15,  387 => 14,  376 => 10,  362 => 9,  351 => 2,  337 => 1,  331 => 486,  327 => 481,  323 => 478,  319 => 472,  315 => 464,  311 => 451,  307 => 438,  303 => 427,  299 => 416,  295 => 410,  291 => 397,  287 => 368,  283 => 362,  279 => 356,  275 => 350,  271 => 344,  267 => 334,  263 => 328,  259 => 319,  255 => 310,  251 => 301,  247 => 288,  243 => 282,  239 => 269,  235 => 263,  231 => 257,  227 => 251,  223 => 242,  219 => 236,  215 => 227,  211 => 221,  207 => 215,  203 => 209,  199 => 203,  195 => 197,  191 => 192,  187 => 189,  183 => 184,  179 => 179,  175 => 174,  171 => 169,  167 => 164,  163 => 159,  159 => 154,  155 => 149,  151 => 144,  147 => 139,  143 => 134,  139 => 129,  135 => 124,  131 => 119,  127 => 114,  123 => 109,  119 => 104,  115 => 99,  111 => 94,  107 => 89,  103 => 84,  99 => 79,  95 => 74,  91 => 69,  87 => 64,  83 => 59,  79 => 54,  75 => 49,  71 => 44,  67 => 39,  63 => 34,  59 => 29,  55 => 24,  51 => 21,  47 => 12,  43 => 7,  39 => 4];
    }

    public function getSourceContext()
    {
        return new Source("{% macro errorList(errors) %}
    {% include \"_includes/forms/errorList\" %}
{% endmacro %}


{# Inputs #}


{% macro button(config) %}
    {% include '_includes/forms/button' with config only %}
{% endmacro %}


{% macro submitButton(config) %}
    {{ _self.button(config|merge({
        type: 'submit',
        class: (config.class ?? [])|explodeClass|merge(['submit']),
        label: config.label ?? 'Submit'|t('app'),
    })) }}
{% endmacro %}


{# Inputs #}


{% macro hidden(config) -%}
    {% include \"_includes/forms/hidden\" with config only %}
{%- endmacro %}


{% macro text(config) %}
    {% include \"_includes/forms/text\" with config only %}
{% endmacro %}


{% macro password(config) %}
    {% include \"_includes/forms/password\" with config only %}
{% endmacro %}


{% macro copytext(config) %}
    {% include \"_includes/forms/copytext\" with config only %}
{% endmacro %}


{% macro date(config) %}
    {% include \"_includes/forms/date\" with config only %}
{% endmacro %}


{% macro time(config) %}
    {% include \"_includes/forms/time\" with config only %}
{% endmacro %}


{% macro color(config) %}
    {% include \"_includes/forms/color\" with config only %}
{% endmacro %}


{% macro colorSelect(config) %}
    {% include \"_includes/forms/colorSelect\" with config only %}
{% endmacro %}


{% macro textarea(config) %}
    {% include \"_includes/forms/textarea\" with config only %}
{% endmacro %}


{% macro select(config) %}
    {% include \"_includes/forms/select\" with config only %}
{% endmacro %}


{% macro customSelect(config) %}
    {% include \"_includes/forms/customSelect\" with config only %}
{% endmacro %}


{% macro selectize(config) %}
    {% include \"_includes/forms/selectize\" with config only %}
{% endmacro %}


{% macro multiselect(config) %}
    {% include \"_includes/forms/multiselect\" with config only %}
{% endmacro %}


{% macro checkbox(config) %}
    {% include \"_includes/forms/checkbox\" with config only %}
{% endmacro %}


{% macro checkboxGroup(config) %}
    {% include \"_includes/forms/checkboxGroup\" with config only %}
{% endmacro %}


{% macro checkboxSelect(config) %}
    {% include \"_includes/forms/checkboxSelect\" with config only %}
{% endmacro %}


{% macro radio(config) %}
    {% include \"_includes/forms/radio\" with config only %}
{% endmacro %}


{% macro radioGroup(config) %}
    {% include \"_includes/forms/radioGroup\" with config only %}
{% endmacro %}


{% macro file(config) %}
    {% include \"_includes/forms/file\" with config only %}
{% endmacro %}


{% macro lightswitch(config) %}
    {% include \"_includes/forms/lightswitch\" with config only %}
{% endmacro %}


{% macro editableTable(config) %}
    {% include \"_includes/forms/editableTable\" with config only %}
{% endmacro %}


{% macro elementSelect(config) %}
    {% include \"_includes/forms/elementSelect\" with config only %}
{% endmacro %}


{% macro componentSelect(config) %}
    {% include \"_includes/forms/componentSelect\" with config only %}
{% endmacro %}


{% macro entryTypeSelect(config) %}
    {% include \"_includes/forms/entryTypeSelect\" with config only %}
{% endmacro %}


{% macro autosuggest(config) %}
    {% include \"_includes/forms/autosuggest\" with config only %}
{% endmacro %}


{% macro timeZone(config) %}
    {% include \"_includes/forms/timeZone\" with config only %}
{% endmacro %}


{% macro iconPicker(config) %}
    {% include '_includes/forms/iconPicker' with config only %}
{% endmacro %}


{% macro fs(config) %}
    {% include \"_includes/forms/fs\" with config only %}
{% endmacro %}


{% macro volume(config) %}
    {% include \"_includes/forms/volume\" with config only %}
{% endmacro %}


{% macro booleanMenu(config) %}
    {% include \"_includes/forms/booleanMenu\" with config only %}
{% endmacro %}


{% macro languageMenu(config) %}
    {% include \"_includes/forms/languageMenu\" with config only %}
{% endmacro %}


{% macro fieldLayoutDesigner(config) %}
    {% include \"_includes/forms/fieldLayoutDesigner\" with config only %}
{% endmacro %}


{% macro money(config) %}
    {% include \"_includes/forms/money\" with config only %}
{% endmacro %}


{# Fields #}


{% macro field(config, input) %}
    {{ craft.cp.field(input ?? '', config)|raw }}
{% endmacro %}


{% macro textField(config) %}
    {% set config = config|merge({id: config.id ?? \"text#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/text') }}
{% endmacro %}


{% macro copytextField(config) %}
    {% set config = config|merge({id: config.id ?? \"copytext#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/copytext') }}
{% endmacro %}


{% macro passwordField(config) %}
    {% set config = config|merge({id: config.id ?? \"password#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/password') }}
{% endmacro %}


{% macro dateField(config) %}
    {% set config = config|merge({id: config.id ?? \"date#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/date') }}
{% endmacro %}


{% macro timeField(config) %}
    {% set config = config|merge({id: config.id ?? \"time#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/time') }}
{% endmacro %}


{% macro colorField(config) %}
    {% set config = config|merge({
        fieldset: true,
        id: config.id ?? \"color#{random()}\"
    }) %}
    {{ _self.field(config, 'template:_includes/forms/color') }}
{% endmacro %}


{% macro colorSelectField(config) %}
    {% set config = config|merge({id: config.id ?? \"colorselect#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/colorSelect') }}
{% endmacro %}


{% macro dateTimeField(config) %}
    {% set config = config|merge({
        id: config.id ?? \"datetime#{random()}\",
        fieldset: true,
    }) %}
    {{ _self.field(config, 'template:_includes/forms/datetime') }}
{% endmacro %}


{% macro textareaField(config) %}
    {% set config = config|merge({id: config.id ?? \"textarea#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/textarea') }}
{% endmacro %}


{% macro selectField(config) %}
    {% set config = config|merge({id: config.id ?? \"select#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/select') }}
{% endmacro %}


{% macro customSelectField(config) %}
    {% set config = config|merge({id: config.id ?? \"customselect#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/customSelect') }}
{% endmacro %}


{% macro selectizeField(config) %}
    {% set config = config|merge({id: config.id ?? \"selectize#{random()}\"}) %}
    {% if (config.includeEnvVars ?? false) and config.tip is not defined and (config.value ?? '')[0:1] != '\$' %}
        {% set config = config|merge({
            tip: (config.allowedEnvValues is not defined)
            ? 'This can be set to an environment variable matching one of the option values.'|t('app')
            : 'This can be set to an environment variable.'|t('app'),
        }) %}
    {% endif %}
    {{ _self.field(config, 'template:_includes/forms/selectize') }}
{% endmacro %}


{% macro multiselectField(config) %}
    {% set config = config|merge({id: config.id ?? \"multiselect#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/multiselect') }}
{% endmacro %}


{% macro checkboxField(config) %}
    {# label --> checkboxLabel #}
    {% set config = config|merge({
        fieldset: config.fieldLabel is defined,
        fieldClass: (config.fieldClass ?? [])|explodeClass|push('checkboxfield'),
        id: config.id ?? \"checkbox#{random()}\",
        checkboxLabel: config.label ?? null,
        instructionsPosition: config.instructionsPosition ?? 'after',
    })|withoutKey('label') %}
    {{ _self.field(config, 'template:_includes/forms/checkbox') }}
{% endmacro %}


{% macro checkboxGroupField(config) %}
    {% set config = config|merge({
        fieldset: true,
        id: config.id ?? \"checkboxgroup#{random()}\",
    }) %}
    {{ _self.field(config, 'template:_includes/forms/checkboxGroup') }}
{% endmacro %}


{% macro checkboxSelectField(config) %}
    {% set config = config|merge({
        fieldset: true,
        id: config.id ?? \"checkboxselect#{random()}\",
    }) %}
    {{ _self.field(config, 'template:_includes/forms/checkboxSelect') }}
{% endmacro %}


{% macro radioGroupField(config) %}
    {% set config = config|merge({
        fieldset: true,
        id: config.id ?? \"radiogroup#{random()}\",
    }) %}
    {{ _self.field(config, 'template:_includes/forms/radioGroup') }}
{% endmacro %}


{% macro fileField(config) %}
    {% set config = config|merge({id: config.id ?? \"file#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/file') }}
{% endmacro %}


{% macro lightswitchField(config) %}
    {% set config = config|merge({
        id: config.id ?? \"lightswitch#{random()}\",
        fieldClass: (config.fieldClass ?? [])|explodeClass|push('lightswitch-field'),
        fieldLabel: config.fieldLabel ?? config.label ?? null,
    })|withoutKey('label') %}
    {{ _self.field(config, 'template:_includes/forms/lightswitch') }}
{% endmacro %}


{% macro editableTableField(config) %}
    {% set config = config|merge({id: config.id ?? \"editabletable#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/editableTable') }}
{% endmacro %}


{% macro elementSelectField(config) %}
    {% set config = config|merge({id: config.id ?? \"elementselect#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/elementSelect') }}
{% endmacro %}


{% macro componentSelectField(config) %}
    {% set config = config|merge({id: config.id ?? \"componentselect#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/componentSelect') }}
{% endmacro %}


{% macro entryTypeSelectField(config) %}
    {% set config = config|merge({id: config.id ?? \"entrytypeselect#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/entryTypeSelect') }}
{% endmacro %}


{% macro autosuggestField(config) %}
    {% set config = config|merge({
        id: config.id ?? \"autosuggest#{random()}\",
    }) %}

    {# Suggest an environment variable / alias? #}
    {% if (config.suggestEnvVars ?? false) %}
        {% set value = config.value ?? '' %}
        {% if config.tip is not defined and value[0:1] not in ['\$', '@'] %}
            {% set config = config|merge({
                tip: ((config.suggestAliases ?? false)
                ? 'This can be set to an environment variable, or begin with an alias.'|t('app')
                : 'This can be set to an environment variable.'|t('app')) ~ ' ' ~ tag('a', {
                    href: 'https://craftcms.com/docs/4.x/config/#control-panel-settings',
                    class: 'go',
                    text: 'Learn more'|t('app'),
                }),
            }) %}
        {% elseif config.warning is not defined and (value == '@web' or value[0:5] == '@web/') and craft.app.request.isWebAliasSetDynamically %}
            {% set config = config|merge({
                warning: 'The `@web` alias is not recommended if it is determined automatically.'|t('app')
            }) %}
        {% endif %}
    {% endif %}

    {{ _self.field(config, 'template:_includes/forms/autosuggest') }}
{% endmacro %}


{% macro timeZoneField(config) %}
    {% set config = config|merge({id: config.id ?? \"timezone#{random()}\"}) %}
    {% if (config.includeEnvVars ?? false) and config.tip is not defined and (config.value ?? '')[0:1] != '\$' %}
        {% set config = config|merge({
            tip: 'This can be set to an environment variable with a value of a [supported time zone]({url}).'|t('app', {
                url: 'https://www.php.net/manual/en/timezones.php',
            }),
        }) %}
    {% endif %}
    {{ _self.field(config, 'template:_includes/forms/timeZone') }}
{% endmacro %}


{% macro iconPickerField(config) %}
    {% set config = config|merge({id: config.id ?? \"iconpicker#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/iconPicker') }}
{% endmacro %}


{% macro fsField(config) %}
    {% set config = config|merge({id: config.id ?? \"fs#{random()}\"}) %}
    {% if (config.includeEnvVars ?? false) and config.tip is not defined and (config.value ?? '')[0:1] != '\$' %}
        {% set config = config|merge({
            tip: 'This can be set to an environment variable matching one of the option values.'|t('app'),
        }) %}
    {% endif %}
    {{ _self.field(config, 'template:_includes/forms/fs') }}
{% endmacro %}


{% macro volumeField(config) %}
    {% set config = config|merge({id: config.id ?? \"volume#{random()}\"}) %}
    {% if (config.includeEnvVars ?? false) and config.tip is not defined and (config.value ?? '')[0:1] != '\$' %}
        {% set config = config|merge({
            tip: 'This can be set to an environment variable matching one of the option values.'|t('app'),
        }) %}
    {% endif %}
    {{ _self.field(config, 'template:_includes/forms/volume') }}
{% endmacro %}


{% macro booleanMenuField(config) %}
    {% set config = config|merge({id: config.id ?? \"booleanmenu#{random()}\"}) %}
    {% if (config.includeEnvVars ?? false) and config.tip is not defined and (config.value ?? '')[0:1] != '\$' %}
        {% set config = config|merge({
            tip: 'This can be set to an environment variable with a boolean value ({examples}).'|t('app', {
                examples: '`yes`/`no`/`true`/`false`/`on`/`off`/`0`/`1`',
            }),
        }) %}
    {% endif %}
    {{ _self.field(config, 'template:_includes/forms/booleanMenu') }}
{% endmacro %}


{% macro languageMenuField(config) %}
    {% set config = config|merge({id: config.id ?? \"languagemenu#{random()}\"}) %}
    {% if (config.includeEnvVars ?? false) and config.tip is not defined and (config.value ?? '')[0:1] != '\$' %}
        {% set config = config|merge({
            tip: 'This can be set to an environment variable with a valid language ID ({examples}).'|t('app', {
                examples: '`en`/`en-GB`',
            }),
        }) %}
    {% endif %}
    {{ _self.field(config, 'template:_includes/forms/languageMenu') }}
{% endmacro %}


{% macro fieldLayoutDesignerField(config) %}
    {{ _self.field({
        label: 'Field Layout'|t('app'),
        errors: (config.fieldLayout ?? false) ? config.fieldLayout.getErrorSummary(true),
    }|merge(config), 'template:_includes/forms/fieldLayoutDesigner') }}
{% endmacro %}


{% macro moneyField(config) %}
    {% set config = config|merge({id: config.id ?? \"money#{random()}\"}) %}
    {{ _self.field(config, 'template:_includes/forms/money') }}
{% endmacro %}


{# Other #}


{% macro optionShortcutLabel(key, shift, alt) -%}
    <span class=\"shortcut\">{{ _self.shortcutText(key, shift, alt) }}</span>
{%- endmacro %}

{% macro shortcutText(key, shift, alt) %}
    {%- switch craft.app.request.getClientOs() %}
    {%- case 'Mac' %}
        {{- (alt ? '⌥') ~ (shift ? '⇧') ~ '⌘' ~ key }}
    {%- default %}
        {{- 'Ctrl+' ~ (alt ? 'Alt+') ~ (shift ? 'Shift+') ~ key }}
    {%- endswitch %}
{%- endmacro %}
", '_includes/forms', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms.twig');
    }
}
