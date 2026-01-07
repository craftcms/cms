<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/disclosuremenu.twig */
class __TwigTemplate_ca4500b09336cb033a148f49936911fa extends Template
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
            'menu' => $this->block_menu(...),
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_includes/disclosuremenu.twig');
        // line 1
        $context['id'] ??= 'menu-'.Twig\Extension\CoreExtension::random($this->env->getCharset());
        // line 2
        $context['withButton'] ??= false;
        // line 3
        $context['buttonSpinner'] ??= false;
        // line 4
        yield '
';
        // line 5
        $context['hasSelected'] = craft\helpers\ArrayHelper::contains(Illuminate\Support\Arr::flatten($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, (isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 5, $this->source);
        })()), function ($__i__) use ($context) {
            $context['i'] = $__i__;

            return ((craft\helpers\Template::attribute($this->env, $this->source, ($context['i'] ?? null), 'items', [], 'any', true, true, false, 5) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['i'] ?? null), 'items', [], 'any', false, false, false, 5) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['i'] ?? null), 'items', [], 'any', false, false, false, 5)) : ([(isset($context['i']) || array_key_exists('i', $context) ? $context['i'] : (function () {
                throw new RuntimeError('Variable "i" does not exist.', 5, $this->source);
            })())]);
        }), 1), 'selected');
        // line 6
        yield '
';
        // line 20
        yield '
';
        // line 24
        yield '
';
        // line 97
        yield '
';
        // line 98
        if ((isset($context['withButton']) || array_key_exists('withButton', $context) ? $context['withButton'] : (function () {
            throw new RuntimeError('Variable "withButton" does not exist.', 98, $this->source);
        })())) {
            // line 99
            yield '  ';
            if ((($context['html']) ?? (false))) {
                // line 100
                yield '    ';
                yield isset($context['html']) || array_key_exists('html', $context) ? $context['html'] : (function () {
                    throw new RuntimeError('Variable "html" does not exist.', 100, $this->source);
                })();
                yield '
  ';
            } elseif (((            // line 101
                $context['label']) ?? (false))) {
                // line 102
                yield '    <span >';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                    throw new RuntimeError('Variable "label" does not exist.', 102, $this->source);
                })()), 'html', null, true);
                yield '</span>
  ';
            }
            // line 104
            yield '
  ';
            // line 105
            ob_start();
            // line 116
            $___internal_parse_1_ = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
                // line 117
                yield '      ';
                if ((isset($context['buttonSpinner']) || array_key_exists('buttonSpinner', $context) ? $context['buttonSpinner'] : (function () {
                    throw new RuntimeError('Variable "buttonSpinner" does not exist.', 117, $this->source);
                })())) {
                    // line 118
                    yield '        <div role="status" class="visually-hidden"></div>
      ';
                }
                // line 120
                yield '      ';
                yield (((isset($context['buttonLabel']) || array_key_exists('buttonLabel', $context) ? $context['buttonLabel'] : (function () {
                    throw new RuntimeError('Variable "buttonLabel" does not exist.', 120, $this->source);
                })()) || (isset($context['buttonHtml']) || array_key_exists('buttonHtml', $context) ? $context['buttonHtml'] : (function () {
                    throw new RuntimeError('Variable "buttonHtml" does not exist.', 120, $this->source);
                })()))) ? ($this->extensions['craft\web\twig\Extension']->tagFunction('div', ['class' => 'label', 'text' => ((                // line 122
                    $context['buttonLabel']) ?? (null)), 'html' => ((                // line 123
                        $context['buttonHtml']) ?? (null))])) : ('');
                // line 124
                yield '
      ';
                // line 125
                if ((isset($context['buttonSpinner']) || array_key_exists('buttonSpinner', $context) ? $context['buttonSpinner'] : (function () {
                    throw new RuntimeError('Variable "buttonSpinner" does not exist.', 125, $this->source);
                })())) {
                    // line 126
                    yield '        <div class="spinner spinner-absolute">
          <span class="visually-hidden">';
                    // line 127
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Loading', 'app'), 'html', null, true);
                    yield '</span>
        </div>
      ';
                }
                // line 130
                yield '    ';
                yield from [];
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 116
            yield Twig\Extension\CoreExtension::spaceless($___internal_parse_1_);
            echo craft\helpers\Html::tag('button', ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => ['btn', 'menubtn'], 'type' => 'button', 'aria' => ['controls' =>             // line 109
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 109, $this->source);
})()), 'label' => ((            // line 110
    $context['hiddenLabel']) ?? (null))], 'data' => ['disclosure-trigger' => true]], ((            // line 115
        $context['buttonAttributes']) ?? ([])), true));
        }
        // line 133
        yield '
';
        // line 134
        ob_start();
        // line 138
        yield '  ';
        yield from $this->unwrap()->yieldBlock('menu', $context, $blocks);
        echo craft\helpers\Html::tag('div', ob_get_clean(), ['id' =>         // line 135
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 135, $this->source);
})()), 'class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass(((        // line 136
    $context['class']) ?? ([]))), ['menu', 'menu--disclosure'])]);
        craft\helpers\Template::endProfile('template', '_includes/disclosuremenu.twig');
        yield from [];
    }

    // line 138
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_menu(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'menu');
        // line 139
        yield '    ';
        $context['ulStarted'] = false;
        // line 140
        yield '    ';
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 140, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 141
            yield '      ';
            $context['headingTag'] = (((craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingTag', [], 'any', true, true, false, 141) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingTag', [], 'any', false, false, false, 141) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingTag', [], 'any', false, false, false, 141)) : ('h3'));
            // line 142
            yield '      ';
            $context['type'] = CoreExtension::callMacro($macros['_self'], 'macro_itemType', [$context['item']], 142, $context, $this->getSourceContext());
            // line 143
            yield '      ';
            if (CoreExtension::inFilter((isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                throw new RuntimeError('Variable "type" does not exist.', 143, $this->source);
            })()), ['hr', 'group'])) {
                // line 144
                yield '        ';
                if ((isset($context['ulStarted']) || array_key_exists('ulStarted', $context) ? $context['ulStarted'] : (function () {
                    throw new RuntimeError('Variable "ulStarted" does not exist.', 144, $this->source);
                })())) {
                    // line 145
                    yield '          ';
                    yield '</ul>';
                    yield '
          ';
                    // line 146
                    $context['ulStarted'] = false;
                    // line 147
                    yield '        ';
                }
                // line 148
                yield '
        ';
                // line 149
                if (((isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                    throw new RuntimeError('Variable "type" does not exist.', 149, $this->source);
                })()) == 'hr')) {
                    // line 150
                    yield '          <hr class="padded">
        ';
                } else {
                    // line 152
                    yield '          ';
                    ob_start();
                    // line 158
                    yield '            ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'heading', [], 'any', true, true, false, 158)) {
                        // line 159
                        yield '              ';
                        ob_start();
                        // line 162
                        yield '                ';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'heading', [], 'any', false, false, false, 162), 'html', null, true);
                        yield '
              ';
                        echo craft\helpers\Html::tag(                        // line 159
                            (isset($context['headingTag']) || array_key_exists('headingTag', $context) ? $context['headingTag'] : (function () {
                                throw new RuntimeError('Variable "headingTag" does not exist.', 159, $this->source);
                            })()), ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => ['h6', 'padded']], (((craft\helpers\Template::attribute($this->env, $this->source,                         // line 161
                                $context['item'], 'headingAttributes', [], 'any', true, true, false, 161) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingAttributes', [], 'any', false, false, false, 161) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingAttributes', [], 'any', false, false, false, 161)) : ([])), true));
                        // line 164
                        yield '            ';
                    }
                    // line 165
                    yield '            ';
                    ob_start();
                    // line 170
                    yield '              ';
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'items', [], 'any', false, false, false, 170));
                    foreach ($context['_seq'] as $context['_key'] => $context['groupItem']) {
                        // line 171
                        yield '                ';
                        yield CoreExtension::callMacro($macros['_self'], 'macro_item', [$context['groupItem'], (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                            throw new RuntimeError('Variable "id" does not exist.', 171, $this->source);
                        })())], 171, $context, $this->getSourceContext());
                        yield '
              ';
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['groupItem'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 173
                    yield '            ';
                    echo craft\helpers\Html::tag('ul', ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['padded' =>                     // line 167
(isset($context['hasSelected']) || array_key_exists('hasSelected', $context) ? $context['hasSelected'] : (function () {
    throw new RuntimeError('Variable "hasSelected" does not exist.', 167, $this->source);
})())]))], (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 169
    $context['item'], 'listAttributes', [], 'any', true, true, false, 169) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'listAttributes', [], 'any', false, false, false, 169) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'listAttributes', [], 'any', false, false, false, 169)) : ([])), true));
                    // line 174
                    yield '          ';
                    echo craft\helpers\Html::tag('div', ob_get_clean(), ['class' => Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['menu-group' => true, 'hidden' => (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 155
                        $context['item'], 'hidden', [], 'any', true, true, false, 155) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'hidden', [], 'any', false, false, false, 155) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'hidden', [], 'any', false, false, false, 155)) : (false))]))]);
                    // line 175
                    yield '        ';
                }
                // line 176
                yield '      ';
            } else {
                // line 177
                yield '        ';
                if (! (isset($context['ulStarted']) || array_key_exists('ulStarted', $context) ? $context['ulStarted'] : (function () {
                    throw new RuntimeError('Variable "ulStarted" does not exist.', 177, $this->source);
                })())) {
                    // line 178
                    yield '          ';
                    if ((isset($context['hasSelected']) || array_key_exists('hasSelected', $context) ? $context['hasSelected'] : (function () {
                        throw new RuntimeError('Variable "hasSelected" does not exist.', 178, $this->source);
                    })())) {
                        // line 179
                        yield '            ';
                        yield '<ul class="padded">';
                        yield '
          ';
                    } else {
                        // line 181
                        yield '            ';
                        yield '<ul>';
                        yield '
          ';
                    }
                    // line 183
                    yield '          ';
                    $context['ulStarted'] = true;
                    // line 184
                    yield '        ';
                }
                // line 185
                yield '        ';
                yield CoreExtension::callMacro($macros['_self'], 'macro_item', [$context['item'], (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                    throw new RuntimeError('Variable "id" does not exist.', 185, $this->source);
                })())], 185, $context, $this->getSourceContext());
                yield '
      ';
            }
            // line 187
            yield '    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 188
        yield '    ';
        if ((isset($context['ulStarted']) || array_key_exists('ulStarted', $context) ? $context['ulStarted'] : (function () {
            throw new RuntimeError('Variable "ulStarted" does not exist.', 188, $this->source);
        })())) {
            yield '</ul>';
        }
        // line 189
        yield '  ';
        craft\helpers\Template::endProfile('block', 'menu');
        yield from [];
    }

    // line 7
    public function macro_itemType($__item__ = null, ...$__varargs__)
    {
        $context = [
            'item' => $__item__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'itemType');
            // line 8
            if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'type', [], 'any', true, true, false, 8) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'type', [], 'any', false, false, false, 8) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'type', [], 'any', false, false, false, 8)) : (false))) {
                // line 9
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 9, $this->source);
                })()), 'type', [], 'any', false, false, false, 9), 'html', null, true);
            } elseif ((((craft\helpers\Template::attribute($this->env, $this->source,             // line 10
                ($context['item'] ?? null), 'url', [], 'any', true, true, false, 10) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'url', [], 'any', false, false, false, 10) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'url', [], 'any', false, false, false, 10)) : (false))) {
                // line 11
                yield 'link';
            } elseif ((((craft\helpers\Template::attribute($this->env, $this->source,             // line 12
                ($context['item'] ?? null), 'hr', [], 'any', true, true, false, 12) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'hr', [], 'any', false, false, false, 12) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'hr', [], 'any', false, false, false, 12)) : (false))) {
                // line 13
                yield 'hr';
            } elseif ((((craft\helpers\Template::attribute($this->env, $this->source,             // line 14
                ($context['item'] ?? null), 'heading', [], 'any', true, true, false, 14) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'heading', [], 'any', false, false, false, 14) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'heading', [], 'any', false, false, false, 14)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'items', [], 'any', true, true, false, 14) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'items', [], 'any', false, false, false, 14) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'items', [], 'any', false, false, false, 14)) : (false))))) {
                // line 15
                yield 'group';
            } else {
                // line 17
                yield 'button';
            }
            craft\helpers\Template::endProfile('macro', 'itemType');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 21
    public function macro_color($__color__ = null, ...$__varargs__)
    {
        $context = [
            'color' => $__color__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'color');
            // line 22
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((($this->env->getTest('instance of')->getCallable()((isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                throw new RuntimeError('Variable "color" does not exist.', 22, $this->source);
            })()), 'craft\\enums\\Color')) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                throw new RuntimeError('Variable "color" does not exist.', 22, $this->source);
            })()), 'value', [], 'any', false, false, false, 22)) : ((isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                throw new RuntimeError('Variable "color" does not exist.', 22, $this->source);
            })()))), 'html', null, true);
            craft\helpers\Template::endProfile('macro', 'color');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 25
    public function macro_item($__item__ = null, $__menuId__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'item' => $__item__,
            'menuId' => $__menuId__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'item');
            // line 26
            yield '  ';
            $context['type'] = CoreExtension::callMacro($macros['_self'], 'macro_itemType', [(isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                throw new RuntimeError('Variable "item" does not exist.', 26, $this->source);
            })())], 26, $context, $this->getSourceContext());
            // line 27
            yield '  ';
            $context['id'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'id', [], 'any', true, true, false, 27) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'id', [], 'any', false, false, false, 27) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'id', [], 'any', false, false, false, 27)) : (('menu-item-'.Twig\Extension\CoreExtension::random($this->env->getCharset()))));
            // line 28
            yield '  ';
            ob_start();
            // line 33
            yield '    ';
            $context['selected'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'selected', [], 'any', true, true, false, 33) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'selected', [], 'any', false, false, false, 33) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'selected', [], 'any', false, false, false, 33)) : (false));
            // line 34
            yield '    ';
            ob_start();
            // line 53
            $___internal_parse_0_ = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
                // line 54
                yield '        ';
                if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'icon', [], 'any', true, true, false, 54) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'icon', [], 'any', false, false, false, 54) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'icon', [], 'any', false, false, false, 54)) : (false))) {
                    // line 55
                    yield '          ';
                    yield $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['icon', CoreExtension::callMacro($macros['_self'], 'macro_color', [(((craft\helpers\Template::attribute($this->env, $this->source,                     // line 58
                        ($context['item'] ?? null), 'color', [], 'any', true, true, false, 58) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'color', [], 'any', false, false, false, 58) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'color', [], 'any', false, false, false, 58)) : (null))], 58, $context, $this->getSourceContext())]), 'html' => craft\helpers\Cp::iconSvg(craft\helpers\Template::attribute($this->env, $this->source,                     // line 60
                            (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                                throw new RuntimeError('Variable "item" does not exist.', 60, $this->source);
                            })()), 'icon', [], 'any', false, false, false, 60))]);
                    // line 61
                    yield '
        ';
                }
                // line 63
                yield '        ';
                if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'status', [], 'any', true, true, false, 63) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'status', [], 'any', false, false, false, 63) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'status', [], 'any', false, false, false, 63)) : (false))) {
                    // line 64
                    yield craft\helpers\Cp::statusIndicatorHtml(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 64, $this->source);
                    })()), 'status', [], 'any', false, false, false, 64));
                }
                // line 66
                yield '<span class="menu-item-label inline-flex flex-col items-start gap-2xs">
          ';
                // line 67
                (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'label', [], 'any', true, true, false, 67) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'label', [], 'any', false, false, false, 67) === null))) ? (yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'label', [], 'any', false, false, false, 67), 'html', null, true)) : (yield craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 67, $this->source);
                })()), 'html', [], 'any', false, false, false, 67)));
                yield '
          ';
                // line 68
                if (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'description', [], 'any', true, true, false, 68)) {
                    // line 69
                    yield '            <span class="menu-item-description mt-2xs smalltext light">';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 69, $this->source);
                    })()), 'description', [], 'any', false, false, false, 69), 'html', null, true);
                    yield '</span>
        ';
                } elseif (craft\helpers\Template::attribute($this->env, $this->source,                 // line 70
                    ($context['item'] ?? null), 'handle', [], 'any', true, true, false, 70)) {
                    // line 71
                    yield '            <span class="menu-item-description mt-2xs smalltext light code">';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 71, $this->source);
                    })()), 'handle', [], 'any', false, false, false, 71), 'html', null, true);
                    yield '</span>
          ';
                }
                // line 73
                yield '        </span>
        ';
                // line 74
                if ((isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                    throw new RuntimeError('Variable "selected" does not exist.', 74, $this->source);
                })())) {
                    // line 75
                    yield '          <span class="visually-hidden">, selected</span>
        ';
                }
                // line 77
                yield '      ';
                yield from [];
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 53
            yield Twig\Extension\CoreExtension::spaceless($___internal_parse_0_);
            echo craft\helpers\Html::tag((((            // line 34
                (isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                    throw new RuntimeError('Variable "type" does not exist.', 34, $this->source);
                })()) == 'button')) ? ('button') : ('a')), ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' =>             // line 35
                (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                    throw new RuntimeError('Variable "id" does not exist.', 35, $this->source);
                })()), 'class' => Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['menu-item' => true, 'sel' =>             // line 38
                (isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                    throw new RuntimeError('Variable "selected" does not exist.', 38, $this->source);
                })()), 'error' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 39
                    ($context['item'] ?? null), 'destructive', [], 'any', true, true, false, 39) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'destructive', [], 'any', false, false, false, 39) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'destructive', [], 'any', false, false, false, 39)) : (false)), 'formsubmit' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 40
                        ($context['item'] ?? null), 'action', [], 'any', true, true, false, 40) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [], 'any', false, false, false, 40) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [], 'any', false, false, false, 40)) : (false))])), 'href' => (((            // line 42
                            (isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                                throw new RuntimeError('Variable "type" does not exist.', 42, $this->source);
                            })()) == 'button')) ? (null) : (craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                                throw new RuntimeError('Variable "item" does not exist.', 42, $this->source);
                            })()), 'url', [], 'any', false, false, false, 42)))), 'data' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['destructive' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 44
                                ($context['item'] ?? null), 'destructive', [], 'any', true, true, false, 44) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'destructive', [], 'any', false, false, false, 44) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'destructive', [], 'any', false, false, false, 44)) : (null)), 'action' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 45
                                    ($context['item'] ?? null), 'action', [], 'any', true, true, false, 45) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [], 'any', false, false, false, 45) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [], 'any', false, false, false, 45)) : (null)), 'params' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 46
                                        ($context['item'] ?? null), 'params', [], 'any', true, true, false, 46) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'params', [], 'any', false, false, false, 46) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'params', [], 'any', false, false, false, 46)) : (null)), 'confirm' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 47
                                            ($context['item'] ?? null), 'confirm', [], 'any', true, true, false, 47) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'confirm', [], 'any', false, false, false, 47) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'confirm', [], 'any', false, false, false, 47)) : (null)), 'redirect' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 48
                                                ($context['item'] ?? null), 'redirect', [], 'any', true, true, false, 48) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'redirect', [], 'any', false, false, false, 48) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'redirect', [], 'any', false, false, false, 48)) : (false))) ? ($this->env->getFilter('hash')->getCallable()(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                                                    throw new RuntimeError('Variable "item" does not exist.', 48, $this->source);
                                                })()), 'redirect', [], 'any', false, false, false, 48))) : (null)), 'require-elevated-session' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 49
                                                    ($context['item'] ?? null), 'requireElevatedSession', [], 'any', true, true, false, 49) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'requireElevatedSession', [], 'any', false, false, false, 49) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'requireElevatedSession', [], 'any', false, false, false, 49)) : (false)), 'form' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 50
                                                        ($context['item'] ?? null), 'action', [], 'any', true, true, false, 50) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [], 'any', false, false, false, 50) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [], 'any', false, false, false, 50)) : (false))) ? ('false') : (null))])], (((craft\helpers\Template::attribute($this->env, $this->source,             // line 52
                                                            ($context['item'] ?? null), 'attributes', [], 'any', true, true, false, 52) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'attributes', [], 'any', false, false, false, 52) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'attributes', [], 'any', false, false, false, 52)) : ([])), true));
            // line 79
            yield '  ';
            echo craft\helpers\Html::tag('li', ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['hidden' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 30
                ($context['item'] ?? null), 'hidden', [], 'any', true, true, false, 30) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'hidden', [], 'any', false, false, false, 30) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'hidden', [], 'any', false, false, false, 30)) : (false))]))], (((craft\helpers\Template::attribute($this->env, $this->source,             // line 32
                    ($context['item'] ?? null), 'liAttributes', [], 'any', true, true, false, 32) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'liAttributes', [], 'any', false, false, false, 32) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'liAttributes', [], 'any', false, false, false, 32)) : ([]))));
            // line 80
            yield '  ';
            if (((isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                throw new RuntimeError('Variable "type" does not exist.', 80, $this->source);
            })()) == 'link')) {
                // line 81
                yield '    ';
                ob_start();
                // line 82
                yield "      \$('#";
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getFilter('namespaceInputId')->getCallable()((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                    throw new RuntimeError('Variable "id" does not exist.', 82, $this->source);
                })())), 'html', null, true);
                yield "').on('keydown', (ev) => {
        if (ev.keyCode === Garnish.SPACE_KEY) {
          ev.currentTarget.click();
        }
      });
    ";
                craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
                // line 88
                yield '  ';
            }
            // line 89
            yield '  ';
            ob_start();
            // line 90
            yield "    \$('#";
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getFilter('namespaceInputId')->getCallable()((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 90, $this->source);
            })())), 'html', null, true);
            yield "').on('activate', () => {
      setTimeout(() => {
        \$('#";
            // line 92
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getFilter('namespaceInputId')->getCallable()((isset($context['menuId']) || array_key_exists('menuId', $context) ? $context['menuId'] : (function () {
                throw new RuntimeError('Variable "menuId" does not exist.', 92, $this->source);
            })())), 'html', null, true);
            yield "').data('disclosureMenu').hide();
      }, 1);
    });
  ";
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
            craft\helpers\Template::endProfile('macro', 'item');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/disclosuremenu.twig';
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
        return [483 => 92,  477 => 90,  474 => 89,  471 => 88,  461 => 82,  458 => 81,  455 => 80,  453 => 32,  452 => 30,  450 => 79,  448 => 52,  447 => 50,  446 => 49,  445 => 48,  444 => 47,  443 => 46,  442 => 45,  441 => 44,  440 => 42,  439 => 40,  438 => 39,  437 => 38,  436 => 35,  435 => 34,  433 => 53,  429 => 77,  425 => 75,  423 => 74,  420 => 73,  414 => 71,  412 => 70,  407 => 69,  405 => 68,  401 => 67,  398 => 66,  395 => 64,  392 => 63,  388 => 61,  386 => 60,  385 => 58,  383 => 55,  380 => 54,  378 => 53,  375 => 34,  372 => 33,  369 => 28,  366 => 27,  363 => 26,  349 => 25,  342 => 22,  329 => 21,  321 => 17,  318 => 15,  316 => 14,  314 => 13,  312 => 12,  310 => 11,  308 => 10,  306 => 9,  304 => 8,  291 => 7,  285 => 189,  280 => 188,  274 => 187,  268 => 185,  265 => 184,  262 => 183,  256 => 181,  250 => 179,  247 => 178,  244 => 177,  241 => 176,  238 => 175,  236 => 155,  234 => 174,  232 => 169,  231 => 167,  229 => 173,  220 => 171,  215 => 170,  212 => 165,  209 => 164,  207 => 161,  206 => 159,  201 => 162,  198 => 159,  195 => 158,  192 => 152,  188 => 150,  186 => 149,  183 => 148,  180 => 147,  178 => 146,  173 => 145,  170 => 144,  167 => 143,  164 => 142,  161 => 141,  156 => 140,  153 => 139,  145 => 138,  139 => 136,  138 => 135,  135 => 138,  133 => 134,  130 => 133,  127 => 115,  126 => 110,  125 => 109,  123 => 116,  119 => 130,  113 => 127,  110 => 126,  108 => 125,  105 => 124,  103 => 123,  102 => 122,  100 => 120,  96 => 118,  93 => 117,  91 => 116,  89 => 105,  86 => 104,  80 => 102,  78 => 101,  73 => 100,  70 => 99,  68 => 98,  65 => 97,  62 => 24,  59 => 20,  56 => 6,  54 => 5,  51 => 4,  49 => 3,  47 => 2,  45 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% set id = id ?? \"menu-#{random()}\" %}
{% set withButton = withButton ?? false %}
{% set buttonSpinner = buttonSpinner ?? false %}

{% set hasSelected = items|map(i => i.items ?? [i])|flatten(1)|contains('selected') %}

{% macro itemType(item) %}
  {%- if item.type ?? false %}
    {{- item.type }}
  {%- elseif item.url ?? false %}
    {{- 'link' }}
  {%- elseif item.hr ?? false %}
    {{- 'hr' }}
  {%- elseif item.heading ?? item.items ?? false %}
    {{- 'group' }}
  {%- else %}
    {{- 'button' }}
  {%- endif %}
{%- endmacro %}

{% macro color(color) %}
  {{- color is instance of ('craft\\\\enums\\\\Color') ? color.value : color -}}
{% endmacro %}

{% macro item(item, menuId) %}
  {% set type = _self.itemType(item) %}
  {% set id = item.id ?? \"menu-item-#{random()}\" %}
  {% tag 'li' with {
    class: {
      hidden: item.hidden ?? false,
    }|filter|keys,
  }|merge(item.liAttributes ?? {}) %}
    {% set selected = item.selected ?? false %}
    {% tag (type == 'button' ? 'button' : 'a') with {
      id: id,
      class: {
        'menu-item': true,
        sel: selected,
        error: item.destructive ?? false,
        formsubmit: item.action ?? false,
      }|filter|keys,
      href: type == 'button' ? null : url(item.url),
      data: {
        destructive: item.destructive ?? null,
        action: item.action ?? null,
        params: item.params ?? null,
        confirm: item.confirm ?? null,
        redirect: (item.redirect ?? false) ? item.redirect|hash : null,
        'require-elevated-session': item.requireElevatedSession ?? false,
        form: (item.action ?? false) ? 'false' : null,
      }|filter,
    }|merge(item.attributes ?? {}, recursive=true) %}
      {%- apply spaceless %}
        {% if item.icon ?? false %}
          {{ tag('span', {
            class: [
              'icon',
              _self.color(item.color ?? null),
            ]|filter,
            html: iconSvg(item.icon),
          }) }}
        {% endif %}
        {% if item.status ?? false -%}
          {{ statusIndicator(item.status) }}
        {%- endif -%}
        <span class=\"menu-item-label inline-flex flex-col items-start gap-2xs\">
          {{ item.label ?? item.html|raw }}
          {% if item.description is defined %}
            <span class=\"menu-item-description mt-2xs smalltext light\">{{ item.description }}</span>
        {% elseif item.handle is defined %}
            <span class=\"menu-item-description mt-2xs smalltext light code\">{{ item.handle }}</span>
          {% endif %}
        </span>
        {% if selected %}
          <span class=\"visually-hidden\">, selected</span>
        {% endif %}
      {% endapply -%}
    {% endtag %}
  {% endtag %}
  {% if type == 'link' %}
    {% js %}
      \$('#{{ id|namespaceInputId }}').on('keydown', (ev) => {
        if (ev.keyCode === Garnish.SPACE_KEY) {
          ev.currentTarget.click();
        }
      });
    {% endjs %}
  {% endif %}
  {% js %}
    \$('#{{ id|namespaceInputId }}').on('activate', () => {
      setTimeout(() => {
        \$('#{{ menuId|namespaceInputId }}').data('disclosureMenu').hide();
      }, 1);
    });
  {% endjs %}
{% endmacro %}

{% if withButton %}
  {% if html ?? false %}
    {{ html|raw }}
  {% elseif label ?? false %}
    <span >{{ label }}</span>
  {% endif %}

  {% tag 'button' with {
    class: ['btn', 'menubtn'],
    type: 'button',
    aria: {
      controls: id,
      label: hiddenLabel ?? null
    },
    data: {
      'disclosure-trigger': true,
    },
  }|merge(buttonAttributes ?? {}, recursive=true) %}
    {%- apply spaceless %}
      {% if buttonSpinner %}
        <div role=\"status\" class=\"visually-hidden\"></div>
      {% endif %}
      {{ (buttonLabel or buttonHtml) ? tag('div', {
        class: 'label',
        text: buttonLabel ?? null,
        html: buttonHtml ?? null
      }) }}
      {% if buttonSpinner %}
        <div class=\"spinner spinner-absolute\">
          <span class=\"visually-hidden\">{{ 'Loading'|t('app') }}</span>
        </div>
      {% endif %}
    {% endapply -%}
  {% endtag %}
{% endif %}

{% tag 'div' with {
  id: id,
  class: (class ?? [])|explodeClass|merge(['menu', 'menu--disclosure']),
} %}
  {% block menu %}
    {% set ulStarted = false %}
    {% for item in items %}
      {% set headingTag = item.headingTag ?? 'h3' %}
      {% set type = _self.itemType(item) %}
      {% if type in ['hr', 'group'] %}
        {% if ulStarted %}
          {{ '</ul>'|raw }}
          {% set ulStarted = false %}
        {% endif %}

        {% if type == 'hr' %}
          <hr class=\"padded\">
        {% else %}
          {% tag 'div' with {
            class: {
              'menu-group': true,
              hidden: item.hidden ?? false,
            }|filter|keys
          } %}
            {% if item.heading is defined %}
              {% tag headingTag with {
                class: ['h6', 'padded'],
              }|merge(item.headingAttributes ??{}, recursive=true) %}
                {{ item.heading }}
              {% endtag %}
            {% endif %}
            {% tag 'ul' with {
              class: {
                padded: hasSelected,
              }|filter|keys,
            }|merge(item.listAttributes ?? {}, recursive=true) %}
              {% for groupItem in item.items %}
                {{ _self.item(groupItem, id) }}
              {% endfor %}
            {% endtag %}
          {% endtag %}
        {% endif %}
      {% else %}
        {% if not ulStarted %}
          {% if hasSelected %}
            {{ '<ul class=\"padded\">'|raw }}
          {% else %}
            {{ '<ul>'|raw }}
          {% endif %}
          {% set ulStarted = true %}
        {% endif %}
        {{ _self.item(item, id) }}
      {% endif %}
    {% endfor %}
    {% if ulStarted %}{{ '</ul>'|raw }}{% endif %}
  {% endblock %}
{% endtag %}
", '_includes/disclosuremenu.twig', '/tmp/packages/craft5/src/templates/_includes/disclosuremenu.twig');
    }
}
