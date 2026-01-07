<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms */
class __TwigTemplate_c201330db1529441b7bc328926e7daa9 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms');
        // line 4
        yield '

';
        // line 7
        yield '

';
        // line 12
        yield '

';
        // line 21
        yield '

';
        // line 24
        yield '

';
        // line 29
        yield '

';
        // line 34
        yield '

';
        // line 39
        yield '

';
        // line 44
        yield '

';
        // line 49
        yield '

';
        // line 54
        yield '

';
        // line 59
        yield '

';
        // line 64
        yield '

';
        // line 69
        yield '

';
        // line 74
        yield '

';
        // line 79
        yield '

';
        // line 84
        yield '

';
        // line 89
        yield '

';
        // line 94
        yield '

';
        // line 99
        yield '

';
        // line 104
        yield '

';
        // line 109
        yield '

';
        // line 114
        yield '

';
        // line 119
        yield '

';
        // line 124
        yield '

';
        // line 129
        yield '

';
        // line 134
        yield '

';
        // line 139
        yield '

';
        // line 144
        yield '

';
        // line 149
        yield '

';
        // line 154
        yield '

';
        // line 159
        yield '

';
        // line 164
        yield '

';
        // line 169
        yield '

';
        // line 174
        yield '

';
        // line 179
        yield '

';
        // line 184
        yield '

';
        // line 189
        yield '

';
        // line 192
        yield '

';
        // line 197
        yield '

';
        // line 203
        yield '

';
        // line 209
        yield '

';
        // line 215
        yield '

';
        // line 221
        yield '

';
        // line 227
        yield '

';
        // line 236
        yield '

';
        // line 242
        yield '

';
        // line 251
        yield '

';
        // line 257
        yield '

';
        // line 263
        yield '

';
        // line 269
        yield '

';
        // line 282
        yield '

';
        // line 288
        yield '

';
        // line 301
        yield '

';
        // line 310
        yield '

';
        // line 319
        yield '

';
        // line 328
        yield '

';
        // line 334
        yield '

';
        // line 344
        yield '

';
        // line 350
        yield '

';
        // line 356
        yield '

';
        // line 362
        yield '

';
        // line 368
        yield '

';
        // line 397
        yield '

';
        // line 410
        yield '

';
        // line 416
        yield '

';
        // line 427
        yield '

';
        // line 438
        yield '

';
        // line 451
        yield '

';
        // line 464
        yield '

';
        // line 472
        yield '

';
        // line 478
        yield '

';
        // line 481
        yield '

';
        // line 486
        yield '
';
        craft\helpers\Template::endProfile('template', '_includes/forms');
        yield from [];
    }

    // line 1
    public function macro_errorList($__errors__ = null, ...$__varargs__)
    {
        $context = [
            'errors' => $__errors__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'errorList');
            // line 2
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/errorList', '_includes/forms', 2)->unwrap()->yield($context);
            craft\helpers\Template::endProfile('macro', 'errorList');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 9
    public function macro_button($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'button');
            // line 10
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/button', '_includes/forms', 10)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 10, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'button');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 14
    public function macro_submitButton($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'submitButton');
            // line 15
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_button', [$this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 15, $this->source);
            })()), ['type' => 'submit', 'class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((((craft\helpers\Template::attribute($this->env, $this->source,             // line 17
                ($context['config'] ?? null), 'class', [], 'any', true, true, false, 17) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'class', [], 'any', false, false, false, 17) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'class', [], 'any', false, false, false, 17)) : ([]))), ['submit']), 'label' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 18
                    ($context['config'] ?? null), 'label', [], 'any', true, true, false, 18) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [], 'any', false, false, false, 18) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [], 'any', false, false, false, 18)) : ($this->extensions['craft\web\twig\Extension']->translateFilter('Submit', 'app')))])], 15, $context, $this->getSourceContext());
            // line 19
            yield '
';
            craft\helpers\Template::endProfile('macro', 'submitButton');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 26
    public function macro_hidden($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'hidden');
            // line 27
            yield from $this->loadTemplate('_includes/forms/hidden', '_includes/forms', 27)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 27, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'hidden');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 31
    public function macro_text($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'text');
            // line 32
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/text', '_includes/forms', 32)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 32, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'text');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 36
    public function macro_password($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'password');
            // line 37
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/password', '_includes/forms', 37)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 37, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'password');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 41
    public function macro_copytext($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'copytext');
            // line 42
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/copytext', '_includes/forms', 42)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 42, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'copytext');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 46
    public function macro_date($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'date');
            // line 47
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/date', '_includes/forms', 47)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 47, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'date');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 51
    public function macro_time($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'time');
            // line 52
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/time', '_includes/forms', 52)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 52, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'time');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 56
    public function macro_color($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'color');
            // line 57
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/color', '_includes/forms', 57)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 57, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'color');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 61
    public function macro_colorSelect($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'colorSelect');
            // line 62
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/colorSelect', '_includes/forms', 62)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 62, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'colorSelect');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 66
    public function macro_textarea($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'textarea');
            // line 67
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/textarea', '_includes/forms', 67)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 67, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'textarea');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 71
    public function macro_select($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'select');
            // line 72
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/select', '_includes/forms', 72)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 72, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'select');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 76
    public function macro_customSelect($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'customSelect');
            // line 77
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/customSelect', '_includes/forms', 77)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 77, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'customSelect');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 81
    public function macro_selectize($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'selectize');
            // line 82
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/selectize', '_includes/forms', 82)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 82, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'selectize');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 86
    public function macro_multiselect($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'multiselect');
            // line 87
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/multiselect', '_includes/forms', 87)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 87, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'multiselect');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 91
    public function macro_checkbox($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'checkbox');
            // line 92
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/checkbox', '_includes/forms', 92)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 92, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'checkbox');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 96
    public function macro_checkboxGroup($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'checkboxGroup');
            // line 97
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/checkboxGroup', '_includes/forms', 97)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 97, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'checkboxGroup');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 101
    public function macro_checkboxSelect($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'checkboxSelect');
            // line 102
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/checkboxSelect', '_includes/forms', 102)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 102, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'checkboxSelect');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 106
    public function macro_radio($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'radio');
            // line 107
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/radio', '_includes/forms', 107)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 107, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'radio');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 111
    public function macro_radioGroup($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'radioGroup');
            // line 112
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/radioGroup', '_includes/forms', 112)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 112, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'radioGroup');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 116
    public function macro_file($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'file');
            // line 117
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/file', '_includes/forms', 117)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 117, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'file');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 121
    public function macro_lightswitch($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'lightswitch');
            // line 122
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/lightswitch', '_includes/forms', 122)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 122, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'lightswitch');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 126
    public function macro_editableTable($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'editableTable');
            // line 127
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/editableTable', '_includes/forms', 127)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 127, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'editableTable');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 131
    public function macro_elementSelect($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'elementSelect');
            // line 132
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/elementSelect', '_includes/forms', 132)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 132, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'elementSelect');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 136
    public function macro_componentSelect($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'componentSelect');
            // line 137
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/componentSelect', '_includes/forms', 137)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 137, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'componentSelect');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 141
    public function macro_entryTypeSelect($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'entryTypeSelect');
            // line 142
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/entryTypeSelect', '_includes/forms', 142)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 142, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'entryTypeSelect');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 146
    public function macro_autosuggest($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'autosuggest');
            // line 147
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/autosuggest', '_includes/forms', 147)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 147, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'autosuggest');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 151
    public function macro_timeZone($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'timeZone');
            // line 152
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/timeZone', '_includes/forms', 152)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 152, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'timeZone');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 156
    public function macro_iconPicker($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'iconPicker');
            // line 157
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/iconPicker', '_includes/forms', 157)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 157, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'iconPicker');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 161
    public function macro_fs($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'fs');
            // line 162
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/fs', '_includes/forms', 162)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 162, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'fs');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 166
    public function macro_volume($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'volume');
            // line 167
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/volume', '_includes/forms', 167)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 167, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'volume');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 171
    public function macro_booleanMenu($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'booleanMenu');
            // line 172
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/booleanMenu', '_includes/forms', 172)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 172, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'booleanMenu');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 176
    public function macro_languageMenu($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'languageMenu');
            // line 177
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/languageMenu', '_includes/forms', 177)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 177, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'languageMenu');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 181
    public function macro_fieldLayoutDesigner($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'fieldLayoutDesigner');
            // line 182
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/fieldLayoutDesigner', '_includes/forms', 182)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 182, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'fieldLayoutDesigner');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 186
    public function macro_money($__config__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'money');
            // line 187
            yield '    ';
            yield from $this->loadTemplate('_includes/forms/money', '_includes/forms', 187)->unwrap()->yield(CoreExtension::toArray((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 187, $this->source);
            })())));
            craft\helpers\Template::endProfile('macro', 'money');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 194
    public function macro_field($__config__ = null, $__input__ = null, ...$__varargs__)
    {
        $context = [
            'config' => $__config__,
            'input' => $__input__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'field');
            // line 195
            yield '    ';
            yield craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 195, $this->source);
            })()), 'cp', [], 'any', false, false, false, 195), 'field', [(($context['input']) ?? ('')), (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 195, $this->source);
            })())], 'method', false, false, false, 195);
            yield '
';
            craft\helpers\Template::endProfile('macro', 'field');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 199
    public function macro_textField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'textField');
            // line 200
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 200, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 200) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 200) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 200)) : (('text'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 201
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 201, $this->source);
            })()), 'template:_includes/forms/text'], 201, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'textField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 205
    public function macro_copytextField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'copytextField');
            // line 206
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 206, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 206) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 206) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 206)) : (('copytext'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 207
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 207, $this->source);
            })()), 'template:_includes/forms/copytext'], 207, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'copytextField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 211
    public function macro_passwordField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'passwordField');
            // line 212
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 212, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 212) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 212) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 212)) : (('password'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 213
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 213, $this->source);
            })()), 'template:_includes/forms/password'], 213, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'passwordField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 217
    public function macro_dateField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'dateField');
            // line 218
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 218, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 218) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 218) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 218)) : (('date'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 219
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 219, $this->source);
            })()), 'template:_includes/forms/date'], 219, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'dateField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 223
    public function macro_timeField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'timeField');
            // line 224
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 224, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 224) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 224) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 224)) : (('time'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 225
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 225, $this->source);
            })()), 'template:_includes/forms/time'], 225, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'timeField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 229
    public function macro_colorField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'colorField');
            // line 230
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 230, $this->source);
            })()), ['fieldset' => true, 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 232
                ($context['config'] ?? null), 'id', [], 'any', true, true, false, 232) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 232) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 232)) : (('color'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 234
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 234, $this->source);
            })()), 'template:_includes/forms/color'], 234, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'colorField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 238
    public function macro_colorSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'colorSelectField');
            // line 239
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 239, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 239) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 239) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 239)) : (('colorselect'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 240
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 240, $this->source);
            })()), 'template:_includes/forms/colorSelect'], 240, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'colorSelectField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 244
    public function macro_dateTimeField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'dateTimeField');
            // line 245
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 245, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 246
                ($context['config'] ?? null), 'id', [], 'any', true, true, false, 246) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 246) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 246)) : (('datetime'.Twig\Extension\CoreExtension::random($this->env->getCharset())))), 'fieldset' => true]);
            // line 249
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 249, $this->source);
            })()), 'template:_includes/forms/datetime'], 249, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'dateTimeField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 253
    public function macro_textareaField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'textareaField');
            // line 254
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 254, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 254) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 254) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 254)) : (('textarea'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 255
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 255, $this->source);
            })()), 'template:_includes/forms/textarea'], 255, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'textareaField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 259
    public function macro_selectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'selectField');
            // line 260
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 260, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 260) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 260) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 260)) : (('select'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 261
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 261, $this->source);
            })()), 'template:_includes/forms/select'], 261, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'selectField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 265
    public function macro_customSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'customSelectField');
            // line 266
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 266, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 266) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 266) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 266)) : (('customselect'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 267
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 267, $this->source);
            })()), 'template:_includes/forms/customSelect'], 267, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'customSelectField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 271
    public function macro_selectizeField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'selectizeField');
            // line 272
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 272, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 272) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 272) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 272)) : (('selectize'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 273
            yield '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true, false, 273) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 273) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 273)) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true, false, 273)) && (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true, false, 273) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 273) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 273)) : ('')), 0, 1) != '$'))) {
                // line 274
                yield '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 274, $this->source);
                })()), ['tip' => ((! craft\helpers\Template::attribute($this->env, $this->source,                 // line 275
                    ($context['config'] ?? null), 'allowedEnvValues', [], 'any', true, true, false, 275)) ? ($this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable matching one of the option values.', 'app')) : ($this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable.', 'app')))]);
                // line 279
                yield '    ';
            }
            // line 280
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 280, $this->source);
            })()), 'template:_includes/forms/selectize'], 280, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'selectizeField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 284
    public function macro_multiselectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'multiselectField');
            // line 285
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 285, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 285) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 285) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 285)) : (('multiselect'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 286
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 286, $this->source);
            })()), 'template:_includes/forms/multiselect'], 286, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'multiselectField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 290
    public function macro_checkboxField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'checkboxField');
            // line 291
            yield '    ';
            // line 292
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->withoutKeyFilter($this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 292, $this->source);
            })()), ['fieldset' => craft\helpers\Template::attribute($this->env, $this->source,             // line 293
                ($context['config'] ?? null), 'fieldLabel', [], 'any', true, true, false, 293), 'fieldClass' => $this->extensions['craft\web\twig\Extension']->pushFilter(craft\helpers\Html::explodeClass((((craft\helpers\Template::attribute($this->env, $this->source,             // line 294
                    ($context['config'] ?? null), 'fieldClass', [], 'any', true, true, false, 294) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldClass', [], 'any', false, false, false, 294) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldClass', [], 'any', false, false, false, 294)) : ([]))), 'checkboxfield'), 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 295
                        ($context['config'] ?? null), 'id', [], 'any', true, true, false, 295) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 295) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 295)) : (('checkbox'.Twig\Extension\CoreExtension::random($this->env->getCharset())))), 'checkboxLabel' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 296
                            ($context['config'] ?? null), 'label', [], 'any', true, true, false, 296) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [], 'any', false, false, false, 296) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [], 'any', false, false, false, 296)) : (null)), 'instructionsPosition' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 297
                                ($context['config'] ?? null), 'instructionsPosition', [], 'any', true, true, false, 297) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'instructionsPosition', [], 'any', false, false, false, 297) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'instructionsPosition', [], 'any', false, false, false, 297)) : ('after'))]), 'label');
            // line 299
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 299, $this->source);
            })()), 'template:_includes/forms/checkbox'], 299, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'checkboxField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 303
    public function macro_checkboxGroupField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'checkboxGroupField');
            // line 304
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 304, $this->source);
            })()), ['fieldset' => true, 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 306
                ($context['config'] ?? null), 'id', [], 'any', true, true, false, 306) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 306) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 306)) : (('checkboxgroup'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 308
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 308, $this->source);
            })()), 'template:_includes/forms/checkboxGroup'], 308, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'checkboxGroupField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 312
    public function macro_checkboxSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'checkboxSelectField');
            // line 313
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 313, $this->source);
            })()), ['fieldset' => true, 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 315
                ($context['config'] ?? null), 'id', [], 'any', true, true, false, 315) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 315) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 315)) : (('checkboxselect'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 317
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 317, $this->source);
            })()), 'template:_includes/forms/checkboxSelect'], 317, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'checkboxSelectField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 321
    public function macro_radioGroupField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'radioGroupField');
            // line 322
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 322, $this->source);
            })()), ['fieldset' => true, 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 324
                ($context['config'] ?? null), 'id', [], 'any', true, true, false, 324) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 324) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 324)) : (('radiogroup'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 326
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 326, $this->source);
            })()), 'template:_includes/forms/radioGroup'], 326, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'radioGroupField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 330
    public function macro_fileField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'fileField');
            // line 331
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 331, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 331) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 331) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 331)) : (('file'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 332
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 332, $this->source);
            })()), 'template:_includes/forms/file'], 332, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'fileField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 336
    public function macro_lightswitchField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'lightswitchField');
            // line 337
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->withoutKeyFilter($this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 337, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 338
                ($context['config'] ?? null), 'id', [], 'any', true, true, false, 338) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 338) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 338)) : (('lightswitch'.Twig\Extension\CoreExtension::random($this->env->getCharset())))), 'fieldClass' => $this->extensions['craft\web\twig\Extension']->pushFilter(craft\helpers\Html::explodeClass((((craft\helpers\Template::attribute($this->env, $this->source,             // line 339
                    ($context['config'] ?? null), 'fieldClass', [], 'any', true, true, false, 339) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldClass', [], 'any', false, false, false, 339) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldClass', [], 'any', false, false, false, 339)) : ([]))), 'lightswitch-field'), 'fieldLabel' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 340
                        ($context['config'] ?? null), 'fieldLabel', [], 'any', true, true, false, 340) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldLabel', [], 'any', false, false, false, 340) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldLabel', [], 'any', false, false, false, 340)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [], 'any', true, true, false, 340) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [], 'any', false, false, false, 340) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'label', [], 'any', false, false, false, 340)) : (null))))]), 'label');
            // line 342
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 342, $this->source);
            })()), 'template:_includes/forms/lightswitch'], 342, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'lightswitchField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 346
    public function macro_editableTableField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'editableTableField');
            // line 347
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 347, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 347) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 347) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 347)) : (('editabletable'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 348
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 348, $this->source);
            })()), 'template:_includes/forms/editableTable'], 348, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'editableTableField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 352
    public function macro_elementSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'elementSelectField');
            // line 353
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 353, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 353) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 353) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 353)) : (('elementselect'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 354
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 354, $this->source);
            })()), 'template:_includes/forms/elementSelect'], 354, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'elementSelectField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 358
    public function macro_componentSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'componentSelectField');
            // line 359
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 359, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 359) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 359) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 359)) : (('componentselect'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 360
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 360, $this->source);
            })()), 'template:_includes/forms/componentSelect'], 360, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'componentSelectField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 364
    public function macro_entryTypeSelectField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'entryTypeSelectField');
            // line 365
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 365, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 365) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 365) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 365)) : (('entrytypeselect'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 366
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 366, $this->source);
            })()), 'template:_includes/forms/entryTypeSelect'], 366, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'entryTypeSelectField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 370
    public function macro_autosuggestField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'autosuggestField');
            // line 371
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 371, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 372
                ($context['config'] ?? null), 'id', [], 'any', true, true, false, 372) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 372) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 372)) : (('autosuggest'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 374
            yield '
    ';
            // line 376
            yield '    ';
            if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestEnvVars', [], 'any', true, true, false, 376) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestEnvVars', [], 'any', false, false, false, 376) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestEnvVars', [], 'any', false, false, false, 376)) : (false))) {
                // line 377
                yield '        ';
                $context['value'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true, false, 377) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 377) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 377)) : (''));
                // line 378
                yield '        ';
                if ((! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true, false, 378) && ! CoreExtension::inFilter(Twig\Extension\CoreExtension::slice($this->env->getCharset(), (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                    throw new RuntimeError('Variable "value" does not exist.', 378, $this->source);
                })()), 0, 1), ['$', '@']))) {
                    // line 379
                    yield '            ';
                    $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                        throw new RuntimeError('Variable "config" does not exist.', 379, $this->source);
                    })()), ['tip' => (((((((craft\helpers\Template::attribute($this->env, $this->source,                     // line 380
                        ($context['config'] ?? null), 'suggestAliases', [], 'any', true, true, false, 380) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestAliases', [], 'any', false, false, false, 380) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'suggestAliases', [], 'any', false, false, false, 380)) : (false))) ? ($this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable, or begin with an alias.', 'app')) : ($this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable.', 'app'))).' ').$this->extensions['craft\web\twig\Extension']->tagFunction('a', ['href' => 'https://craftcms.com/docs/5.x/configure.html#control-panel-settings', 'class' => 'go', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Learn more', 'app')]))]);
                    // line 388
                    yield '        ';
                } elseif ((! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'warning', [], 'any', true, true, false, 388) && (((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                    throw new RuntimeError('Variable "value" does not exist.', 388, $this->source);
                })()) == '@web') || (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                    throw new RuntimeError('Variable "value" does not exist.', 388, $this->source);
                })()), 0, 5) == '@web/')))) {
                    // line 389
                    yield '            ';
                    $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                        throw new RuntimeError('Variable "config" does not exist.', 389, $this->source);
                    })()), ['warning' => $this->extensions['craft\web\twig\Extension']->translateFilter('The `@web` alias is not recommended.', 'app')]);
                    // line 392
                    yield '        ';
                }
                // line 393
                yield '    ';
            }
            // line 394
            yield '
    ';
            // line 395
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 395, $this->source);
            })()), 'template:_includes/forms/autosuggest'], 395, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'autosuggestField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 399
    public function macro_timeZoneField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'timeZoneField');
            // line 400
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 400, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 400) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 400) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 400)) : (('timezone'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 401
            yield '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true, false, 401) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 401) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 401)) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true, false, 401)) && (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true, false, 401) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 401) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 401)) : ('')), 0, 1) != '$'))) {
                // line 402
                yield '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 402, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable with a value of a [supported time zone]({url}).', 'app', ['url' => 'https://www.php.net/manual/en/timezones.php'])]);
                // line 407
                yield '    ';
            }
            // line 408
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 408, $this->source);
            })()), 'template:_includes/forms/timeZone'], 408, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'timeZoneField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 412
    public function macro_iconPickerField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'iconPickerField');
            // line 413
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 413, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 413) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 413) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 413)) : (('iconpicker'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 414
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 414, $this->source);
            })()), 'template:_includes/forms/iconPicker'], 414, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'iconPickerField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 418
    public function macro_fsField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'fsField');
            // line 419
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 419, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 419) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 419) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 419)) : (('fs'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 420
            yield '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true, false, 420) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 420) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 420)) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true, false, 420)) && (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true, false, 420) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 420) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 420)) : ('')), 0, 1) != '$'))) {
                // line 421
                yield '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 421, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable matching one of the option values.', 'app')]);
                // line 424
                yield '    ';
            }
            // line 425
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 425, $this->source);
            })()), 'template:_includes/forms/fs'], 425, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'fsField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 429
    public function macro_volumeField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'volumeField');
            // line 430
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 430, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 430) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 430) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 430)) : (('volume'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 431
            yield '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true, false, 431) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 431) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 431)) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true, false, 431)) && (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true, false, 431) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 431) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 431)) : ('')), 0, 1) != '$'))) {
                // line 432
                yield '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 432, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable matching one of the option values.', 'app')]);
                // line 435
                yield '    ';
            }
            // line 436
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 436, $this->source);
            })()), 'template:_includes/forms/volume'], 436, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'volumeField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 440
    public function macro_booleanMenuField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'booleanMenuField');
            // line 441
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 441, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 441) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 441) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 441)) : (('booleanmenu'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 442
            yield '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true, false, 442) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 442) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 442)) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true, false, 442)) && (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true, false, 442) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 442) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 442)) : ('')), 0, 1) != '$'))) {
                // line 443
                yield '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 443, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable with a boolean value ({examples}).', 'app', ['examples' => '`yes`/`no`/`true`/`false`/`on`/`off`/`0`/`1`'])]);
                // line 448
                yield '    ';
            }
            // line 449
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 449, $this->source);
            })()), 'template:_includes/forms/booleanMenu'], 449, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'booleanMenuField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 453
    public function macro_languageMenuField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'languageMenuField');
            // line 454
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 454, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 454) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 454) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 454)) : (('languagemenu'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 455
            yield '    ';
            if ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', true, true, false, 455) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 455) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'includeEnvVars', [], 'any', false, false, false, 455)) : (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'tip', [], 'any', true, true, false, 455)) && (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', true, true, false, 455) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 455) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'value', [], 'any', false, false, false, 455)) : ('')), 0, 1) != '$'))) {
                // line 456
                yield '        ';
                $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 456, $this->source);
                })()), ['tip' => $this->extensions['craft\web\twig\Extension']->translateFilter('This can be set to an environment variable with a valid language ID ({examples}).', 'app', ['examples' => '`en`/`en-GB`'])]);
                // line 461
                yield '    ';
            }
            // line 462
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 462, $this->source);
            })()), 'template:_includes/forms/languageMenu'], 462, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'languageMenuField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 466
    public function macro_fieldLayoutDesignerField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'fieldLayoutDesignerField');
            // line 467
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [$this->extensions['craft\web\twig\Extension']->mergeFilter(['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Field Layout', 'app'), 'errors' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 469
                ($context['config'] ?? null), 'fieldLayout', [], 'any', true, true, false, 469) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldLayout', [], 'any', false, false, false, 469) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'fieldLayout', [], 'any', false, false, false, 469)) : (false))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 469, $this->source);
                })()), 'fieldLayout', [], 'any', false, false, false, 469), 'getErrorSummary', [true], 'method', false, false, false, 469)) : (''))],             // line 470
                (isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                    throw new RuntimeError('Variable "config" does not exist.', 470, $this->source);
                })())), 'template:_includes/forms/fieldLayoutDesigner'], 467, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'fieldLayoutDesignerField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 474
    public function macro_moneyField($__config__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'config' => $__config__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'moneyField');
            // line 475
            yield '    ';
            $context['config'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 475, $this->source);
            })()), ['id' => (((craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', true, true, false, 475) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 475) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['config'] ?? null), 'id', [], 'any', false, false, false, 475)) : (('money'.Twig\Extension\CoreExtension::random($this->env->getCharset()))))]);
            // line 476
            yield '    ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_field', [(isset($context['config']) || array_key_exists('config', $context) ? $context['config'] : (function () {
                throw new RuntimeError('Variable "config" does not exist.', 476, $this->source);
            })()), 'template:_includes/forms/money'], 476, $context, $this->getSourceContext());
            yield '
';
            craft\helpers\Template::endProfile('macro', 'moneyField');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 483
    public function macro_optionShortcutLabel($__key__ = null, $__shift__ = null, $__alt__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'key' => $__key__,
            'shift' => $__shift__,
            'alt' => $__alt__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'optionShortcutLabel');
            // line 484
            yield '<span class="shortcut">';
            yield CoreExtension::callMacro($macros['_self'], 'macro_shortcutText', [(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                throw new RuntimeError('Variable "key" does not exist.', 484, $this->source);
            })()), (isset($context['shift']) || array_key_exists('shift', $context) ? $context['shift'] : (function () {
                throw new RuntimeError('Variable "shift" does not exist.', 484, $this->source);
            })()), (isset($context['alt']) || array_key_exists('alt', $context) ? $context['alt'] : (function () {
                throw new RuntimeError('Variable "alt" does not exist.', 484, $this->source);
            })())], 484, $context, $this->getSourceContext());
            yield '</span>';
            craft\helpers\Template::endProfile('macro', 'optionShortcutLabel');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 487
    public function macro_shortcutText($__key__ = null, $__shift__ = null, $__alt__ = null, ...$__varargs__)
    {
        $context = [
            'key' => $__key__,
            'shift' => $__shift__,
            'alt' => $__alt__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'shortcutText');
            // line 488
            match (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 488, $this->source);
            })()), 'app', [], 'any', false, false, false, 488), 'request', [], 'any', false, false, false, 488), 'getClientOs', [], 'method', false, false, false, 488)) {
                // line 490
                'Mac' => yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(((((((isset($context['alt']) || array_key_exists('alt', $context) ? $context['alt'] : (function () {
                    throw new RuntimeError('Variable "alt" does not exist.', 490, $this->source);
                })())) ? ('⌥') : ('')).(((isset($context['shift']) || array_key_exists('shift', $context) ? $context['shift'] : (function () {
                    throw new RuntimeError('Variable "shift" does not exist.', 490, $this->source);
                })())) ? ('⇧') : (''))).'⌘').(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                    throw new RuntimeError('Variable "key" does not exist.', 490, $this->source);
                })())), 'html', null, true),
                // line 492
                default => yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(((('Ctrl+'.(((isset($context['alt']) || array_key_exists('alt', $context) ? $context['alt'] : (function () {
                    throw new RuntimeError('Variable "alt" does not exist.', 492, $this->source);
                })())) ? ('Alt+') : (''))).(((isset($context['shift']) || array_key_exists('shift', $context) ? $context['shift'] : (function () {
                    throw new RuntimeError('Variable "shift" does not exist.', 492, $this->source);
                })())) ? ('Shift+') : (''))).(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                    throw new RuntimeError('Variable "key" does not exist.', 492, $this->source);
                })())), 'html', null, true),
            };
            craft\helpers\Template::endProfile('macro', 'shortcutText');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms';
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
        return [2108 => 492,  2102 => 490,  2098 => 488,  2083 => 487,  2074 => 484,  2059 => 483,  2049 => 476,  2046 => 475,  2033 => 474,  2024 => 470,  2023 => 469,  2021 => 467,  2008 => 466,  1998 => 462,  1995 => 461,  1992 => 456,  1989 => 455,  1986 => 454,  1973 => 453,  1963 => 449,  1960 => 448,  1957 => 443,  1954 => 442,  1951 => 441,  1938 => 440,  1928 => 436,  1925 => 435,  1922 => 432,  1919 => 431,  1916 => 430,  1903 => 429,  1893 => 425,  1890 => 424,  1887 => 421,  1884 => 420,  1881 => 419,  1868 => 418,  1858 => 414,  1855 => 413,  1842 => 412,  1832 => 408,  1829 => 407,  1826 => 402,  1823 => 401,  1820 => 400,  1807 => 399,  1798 => 395,  1795 => 394,  1792 => 393,  1789 => 392,  1786 => 389,  1783 => 388,  1781 => 380,  1779 => 379,  1776 => 378,  1773 => 377,  1770 => 376,  1767 => 374,  1765 => 372,  1763 => 371,  1750 => 370,  1740 => 366,  1737 => 365,  1724 => 364,  1714 => 360,  1711 => 359,  1698 => 358,  1688 => 354,  1685 => 353,  1672 => 352,  1662 => 348,  1659 => 347,  1646 => 346,  1636 => 342,  1634 => 340,  1633 => 339,  1632 => 338,  1630 => 337,  1617 => 336,  1607 => 332,  1604 => 331,  1591 => 330,  1581 => 326,  1579 => 324,  1577 => 322,  1564 => 321,  1554 => 317,  1552 => 315,  1550 => 313,  1537 => 312,  1527 => 308,  1525 => 306,  1523 => 304,  1510 => 303,  1500 => 299,  1498 => 297,  1497 => 296,  1496 => 295,  1495 => 294,  1494 => 293,  1492 => 292,  1490 => 291,  1477 => 290,  1467 => 286,  1464 => 285,  1451 => 284,  1441 => 280,  1438 => 279,  1436 => 275,  1434 => 274,  1431 => 273,  1428 => 272,  1415 => 271,  1405 => 267,  1402 => 266,  1389 => 265,  1379 => 261,  1376 => 260,  1363 => 259,  1353 => 255,  1350 => 254,  1337 => 253,  1327 => 249,  1325 => 246,  1323 => 245,  1310 => 244,  1300 => 240,  1297 => 239,  1284 => 238,  1274 => 234,  1272 => 232,  1270 => 230,  1257 => 229,  1247 => 225,  1244 => 224,  1231 => 223,  1221 => 219,  1218 => 218,  1205 => 217,  1195 => 213,  1192 => 212,  1179 => 211,  1169 => 207,  1166 => 206,  1153 => 205,  1143 => 201,  1140 => 200,  1127 => 199,  1117 => 195,  1103 => 194,  1095 => 187,  1082 => 186,  1074 => 182,  1061 => 181,  1053 => 177,  1040 => 176,  1032 => 172,  1019 => 171,  1011 => 167,  998 => 166,  990 => 162,  977 => 161,  969 => 157,  956 => 156,  948 => 152,  935 => 151,  927 => 147,  914 => 146,  906 => 142,  893 => 141,  885 => 137,  872 => 136,  864 => 132,  851 => 131,  843 => 127,  830 => 126,  822 => 122,  809 => 121,  801 => 117,  788 => 116,  780 => 112,  767 => 111,  759 => 107,  746 => 106,  738 => 102,  725 => 101,  717 => 97,  704 => 96,  696 => 92,  683 => 91,  675 => 87,  662 => 86,  654 => 82,  641 => 81,  633 => 77,  620 => 76,  612 => 72,  599 => 71,  591 => 67,  578 => 66,  570 => 62,  557 => 61,  549 => 57,  536 => 56,  528 => 52,  515 => 51,  507 => 47,  494 => 46,  486 => 42,  473 => 41,  465 => 37,  452 => 36,  444 => 32,  431 => 31,  424 => 27,  411 => 26,  403 => 19,  401 => 18,  400 => 17,  398 => 15,  385 => 14,  377 => 10,  364 => 9,  356 => 2,  343 => 1,  336 => 486,  332 => 481,  328 => 478,  324 => 472,  320 => 464,  316 => 451,  312 => 438,  308 => 427,  304 => 416,  300 => 410,  296 => 397,  292 => 368,  288 => 362,  284 => 356,  280 => 350,  276 => 344,  272 => 334,  268 => 328,  264 => 319,  260 => 310,  256 => 301,  252 => 288,  248 => 282,  244 => 269,  240 => 263,  236 => 257,  232 => 251,  228 => 242,  224 => 236,  220 => 227,  216 => 221,  212 => 215,  208 => 209,  204 => 203,  200 => 197,  196 => 192,  192 => 189,  188 => 184,  184 => 179,  180 => 174,  176 => 169,  172 => 164,  168 => 159,  164 => 154,  160 => 149,  156 => 144,  152 => 139,  148 => 134,  144 => 129,  140 => 124,  136 => 119,  132 => 114,  128 => 109,  124 => 104,  120 => 99,  116 => 94,  112 => 89,  108 => 84,  104 => 79,  100 => 74,  96 => 69,  92 => 64,  88 => 59,  84 => 54,  80 => 49,  76 => 44,  72 => 39,  68 => 34,  64 => 29,  60 => 24,  56 => 21,  52 => 12,  48 => 7,  44 => 4];
    }

    public function getSourceContext(): Source
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
                    href: 'https://craftcms.com/docs/5.x/configure.html#control-panel-settings',
                    class: 'go',
                    text: 'Learn more'|t('app'),
                }),
            }) %}
        {% elseif config.warning is not defined and (value == '@web' or value[0:5] == '@web/') %}
            {% set config = config|merge({
                warning: 'The `@web` alias is not recommended.'|t('app'),
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
", '_includes/forms', '/tmp/packages/craft5/src/templates/_includes/forms.twig');
    }
}
