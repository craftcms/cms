<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/disclosuremenu.twig */
class __TwigTemplate_2ec2bb502a888f424f5f6a795070c3b8 extends Template
{
    private $source;

    private $macros = [];

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

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_includes/disclosuremenu.twig');
        // line 1
        $context['id'] ??= 'menu-'.twig_random($this->env);
        // line 2
        $context['withButton'] ??= false;
        // line 3
        $context['buttonSpinner'] ??= false;
        // line 4
        echo '
';
        // line 5
        $context['hasSelected'] = craft\helpers\ArrayHelper::contains(Illuminate\Support\Arr::flatten($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, (isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 5, $this->source);
        })()), function ($__i__) use ($context) {
            $context['i'] = $__i__;

            return ((craft\helpers\Template::attribute($this->env, $this->source, ($context['i'] ?? null), 'items', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['i'] ?? null), 'items', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['i'] ?? null), 'items', [])) : ([0 => (isset($context['i']) || array_key_exists('i', $context) ? $context['i'] : (function () {
                throw new RuntimeError('Variable "i" does not exist.', 5, $this->source);
            })())]);
        }), 1), 'selected');
        // line 6
        echo '
';
        // line 20
        echo '
';
        // line 24
        echo '
';
        // line 82
        echo '
';
        // line 83
        if ((isset($context['withButton']) || array_key_exists('withButton', $context) ? $context['withButton'] : (function () {
            throw new RuntimeError('Variable "withButton" does not exist.', 83, $this->source);
        })())) {
            // line 84
            echo '  ';
            if ((($context['html']) ?? (false))) {
                // line 85
                echo '    ';
                echo isset($context['html']) || array_key_exists('html', $context) ? $context['html'] : (function () {
                    throw new RuntimeError('Variable "html" does not exist.', 85, $this->source);
                })();
                echo '
  ';
            } elseif (((            // line 86
                $context['label']) ?? (false))) {
                // line 87
                echo '    <span >';
                echo twig_escape_filter($this->env, (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                    throw new RuntimeError('Variable "label" does not exist.', 87, $this->source);
                })()), 'html', null, true);
                echo '</span>
  ';
            }
            // line 89
            echo '
  ';
            // line 90
            ob_start();
            // line 101
            ob_start();
            // line 102
            echo '      ';
            if ((isset($context['buttonSpinner']) || array_key_exists('buttonSpinner', $context) ? $context['buttonSpinner'] : (function () {
                throw new RuntimeError('Variable "buttonSpinner" does not exist.', 102, $this->source);
            })())) {
                // line 103
                echo '        <div role="status" class="visually-hidden"></div>
      ';
            }
            // line 105
            echo '      ';
            echo (((isset($context['buttonLabel']) || array_key_exists('buttonLabel', $context) ? $context['buttonLabel'] : (function () {
                throw new RuntimeError('Variable "buttonLabel" does not exist.', 105, $this->source);
            })()) || (isset($context['buttonHtml']) || array_key_exists('buttonHtml', $context) ? $context['buttonHtml'] : (function () {
                throw new RuntimeError('Variable "buttonHtml" does not exist.', 105, $this->source);
            })()))) ? ($this->extensions['craft\web\twig\Extension']->tagFunction('div', ['class' => 'label', 'text' => ((            // line 107
                $context['buttonLabel']) ?? (null)), 'html' => ((            // line 108
                    $context['buttonHtml']) ?? (null))])) : ('');
            // line 109
            echo '
      ';
            // line 110
            if ((isset($context['buttonSpinner']) || array_key_exists('buttonSpinner', $context) ? $context['buttonSpinner'] : (function () {
                throw new RuntimeError('Variable "buttonSpinner" does not exist.', 110, $this->source);
            })())) {
                // line 111
                echo '        <div class="spinner spinner-absolute">
          <span class="visually-hidden">';
                // line 112
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Loading', 'app'), 'html', null, true);
                echo '</span>
        </div>
      ';
            }
            // line 115
            echo '    ';
            $___internal_parse_1_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 101
            echo twig_spaceless($___internal_parse_1_);
            echo craft\helpers\Html::tag('button', ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => [0 => 'btn', 1 => 'menubtn'], 'type' => 'button', 'aria' => ['controls' =>             // line 94
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 94, $this->source);
})()), 'label' => ((            // line 95
    $context['hiddenLabel']) ?? (null)), ], 'data' => ['disclosure-trigger' => true]], ((            // line 100
        $context['buttonAttributes']) ?? ([])), true));
        }
        // line 118
        echo '
';
        // line 119
        ob_start();
        // line 123
        echo '  ';
        $this->displayBlock('menu', $context, $blocks);
        echo craft\helpers\Html::tag('div', ob_get_clean(), ['id' =>         // line 120
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 120, $this->source);
})()), 'class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass(((        // line 121
    $context['class']) ?? ([]))), [0 => 'menu', 1 => 'menu--disclosure']), ]);
        craft\helpers\Template::endProfile('template', '_includes/disclosuremenu.twig');
    }

    // line 123
    public function block_menu($context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'menu');
        // line 124
        echo '    ';
        $context['ulStarted'] = false;
        // line 125
        echo '    ';
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 125, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 126
            echo '      ';
            $context['headingTag'] = (((craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingTag', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingTag', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingTag', [])) : ('h3'));
            // line 127
            echo '      ';
            $context['type'] = twig_call_macro($macros['_self'], 'macro_itemType', [$context['item']], 127, $context, $this->getSourceContext());
            // line 128
            echo '      ';
            if (twig_in_filter((isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                throw new RuntimeError('Variable "type" does not exist.', 128, $this->source);
            })()), [0 => 'hr', 1 => 'group'])) {
                // line 129
                echo '        ';
                if ((isset($context['ulStarted']) || array_key_exists('ulStarted', $context) ? $context['ulStarted'] : (function () {
                    throw new RuntimeError('Variable "ulStarted" does not exist.', 129, $this->source);
                })())) {
                    // line 130
                    echo '          ';
                    echo '</ul>';
                    echo '
          ';
                    // line 131
                    $context['ulStarted'] = false;
                    // line 132
                    echo '        ';
                }
                // line 133
                echo '
        ';
                // line 134
                if (((isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                    throw new RuntimeError('Variable "type" does not exist.', 134, $this->source);
                })()) == 'hr')) {
                    // line 135
                    echo '          <hr class="padded">
        ';
                } else {
                    // line 137
                    echo '          ';
                    ob_start();
                    // line 143
                    echo '            ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'heading', [], 'any', true, true)) {
                        // line 144
                        echo '              ';
                        ob_start();
                        // line 147
                        echo '                ';
                        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'heading', []), 'html', null, true);
                        echo '
              ';
                        echo craft\helpers\Html::tag(                        // line 144
                            (isset($context['headingTag']) || array_key_exists('headingTag', $context) ? $context['headingTag'] : (function () {
                                throw new RuntimeError('Variable "headingTag" does not exist.', 144, $this->source);
                            })()), ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => [0 => 'h6', 1 => 'padded']], (((craft\helpers\Template::attribute($this->env, $this->source,                         // line 146
                                $context['item'], 'headingAttributes', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingAttributes', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'headingAttributes', [])) : ([])), true));
                        // line 149
                        echo '            ';
                    }
                    // line 150
                    echo '            ';
                    ob_start();
                    // line 155
                    echo '              ';
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'items', []));
                    foreach ($context['_seq'] as $context['_key'] => $context['groupItem']) {
                        // line 156
                        echo '                ';
                        echo twig_call_macro($macros['_self'], 'macro_item', [$context['groupItem'], (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                            throw new RuntimeError('Variable "id" does not exist.', 156, $this->source);
                        })())], 156, $context, $this->getSourceContext());
                        echo '
              ';
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['groupItem'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 158
                    echo '            ';
                    echo craft\helpers\Html::tag('ul', ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['padded' =>                     // line 152
(isset($context['hasSelected']) || array_key_exists('hasSelected', $context) ? $context['hasSelected'] : (function () {
    throw new RuntimeError('Variable "hasSelected" does not exist.', 152, $this->source);
})()), ]))], (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 154
    $context['item'], 'listAttributes', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'listAttributes', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'listAttributes', [])) : ([])), true));
                    // line 159
                    echo '          ';
                    echo craft\helpers\Html::tag('div', ob_get_clean(), ['class' => twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['menu-group' => true, 'hidden' => (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 140
                        $context['item'], 'hidden', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'hidden', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'hidden', [])) : (false))]))]);
                    // line 160
                    echo '        ';
                }
                // line 161
                echo '      ';
            } else {
                // line 162
                echo '        ';
                if (! (isset($context['ulStarted']) || array_key_exists('ulStarted', $context) ? $context['ulStarted'] : (function () {
                    throw new RuntimeError('Variable "ulStarted" does not exist.', 162, $this->source);
                })())) {
                    // line 163
                    echo '          ';
                    if ((isset($context['hasSelected']) || array_key_exists('hasSelected', $context) ? $context['hasSelected'] : (function () {
                        throw new RuntimeError('Variable "hasSelected" does not exist.', 163, $this->source);
                    })())) {
                        // line 164
                        echo '            ';
                        echo '<ul class="padded">';
                        echo '
          ';
                    } else {
                        // line 166
                        echo '            ';
                        echo '<ul>';
                        echo '
          ';
                    }
                    // line 168
                    echo '          ';
                    $context['ulStarted'] = true;
                    // line 169
                    echo '        ';
                }
                // line 170
                echo '        ';
                echo twig_call_macro($macros['_self'], 'macro_item', [$context['item'], (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                    throw new RuntimeError('Variable "id" does not exist.', 170, $this->source);
                })())], 170, $context, $this->getSourceContext());
                echo '
      ';
            }
            // line 172
            echo '    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 173
        echo '    ';
        if ((isset($context['ulStarted']) || array_key_exists('ulStarted', $context) ? $context['ulStarted'] : (function () {
            throw new RuntimeError('Variable "ulStarted" does not exist.', 173, $this->source);
        })())) {
            echo '</ul>';
        }
        // line 174
        echo '  ';
        craft\helpers\Template::endProfile('block', 'menu');
    }

    // line 7
    public function macro_itemType($__item__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'item' => $__item__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'itemType');
            // line 8
            if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'type', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'type', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'type', [])) : (false))) {
                // line 9
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 9, $this->source);
                })()), 'type', []), 'html', null, true);
            } elseif ((((craft\helpers\Template::attribute($this->env, $this->source,             // line 10
                ($context['item'] ?? null), 'url', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'url', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'url', [])) : (false))) {
                // line 11
                echo 'link';
            } elseif ((((craft\helpers\Template::attribute($this->env, $this->source,             // line 12
                ($context['item'] ?? null), 'hr', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'hr', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'hr', [])) : (false))) {
                // line 13
                echo 'hr';
            } elseif ((((craft\helpers\Template::attribute($this->env, $this->source,             // line 14
                ($context['item'] ?? null), 'heading', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'heading', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'heading', [])) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'items', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'items', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'items', [])) : (false))))) {
                // line 15
                echo 'group';
            } else {
                // line 17
                echo 'button';
            }
            craft\helpers\Template::endProfile('macro', 'itemType');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 21
    public function macro_color($__color__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'color' => $__color__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'color');
            // line 22
            echo twig_escape_filter($this->env, (($this->env->getTest('instance of')->getCallable()((isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                throw new RuntimeError('Variable "color" does not exist.', 22, $this->source);
            })()), 'craft\\enums\\Color')) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                throw new RuntimeError('Variable "color" does not exist.', 22, $this->source);
            })()), 'value', [])) : ((isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                throw new RuntimeError('Variable "color" does not exist.', 22, $this->source);
            })()))), 'html', null, true);
            craft\helpers\Template::endProfile('macro', 'color');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 25
    public function macro_item($__item__ = null, $__menuId__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'item' => $__item__,
            'menuId' => $__menuId__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'item');
            // line 26
            echo '  ';
            $context['type'] = twig_call_macro($macros['_self'], 'macro_itemType', [(isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                throw new RuntimeError('Variable "item" does not exist.', 26, $this->source);
            })())], 26, $context, $this->getSourceContext());
            // line 27
            echo '  ';
            $context['id'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'id', [])) : (('menu-item-'.twig_random($this->env))));
            // line 28
            echo '  ';
            ob_start();
            // line 33
            echo '    ';
            $context['selected'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'selected', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'selected', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'selected', [])) : (false));
            // line 34
            echo '    ';
            ob_start();
            // line 53
            ob_start();
            // line 54
            echo '        ';
            if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'icon', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'icon', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'icon', [])) : (false))) {
                // line 55
                echo '          ';
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'icon', 1 => twig_call_macro($macros['_self'], 'macro_color', [(((craft\helpers\Template::attribute($this->env, $this->source,                 // line 58
                    ($context['item'] ?? null), 'color', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'color', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'color', [])) : (null))], 58, $context, $this->getSourceContext())]), 'html' => craft\helpers\Cp::iconSvg(craft\helpers\Template::attribute($this->env, $this->source,                 // line 60
                        (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                            throw new RuntimeError('Variable "item" does not exist.', 60, $this->source);
                        })()), 'icon', []))]);
                // line 61
                echo '
        ';
            }
            // line 63
            echo '        ';
            if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'status', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'status', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'status', [])) : (false))) {
                // line 64
                echo craft\helpers\Cp::statusIndicatorHtml(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 64, $this->source);
                })()), 'status', []));
            }
            // line 66
            echo '<span class="menu-item-label">';
            (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'label', []) === null))) ? (print twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'label', []), 'html', null, true)) : (print craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                throw new RuntimeError('Variable "item" does not exist.', 66, $this->source);
            })()), 'html', [])));
            echo '</span>
        ';
            // line 67
            if (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'description', [], 'any', true, true)) {
                // line 68
                echo '          <div class="menu-item-description smalltext light">';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 68, $this->source);
                })()), 'description', []), 'html', null, true);
                echo '</div>
        ';
            }
            // line 70
            echo '        ';
            if ((isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                throw new RuntimeError('Variable "selected" does not exist.', 70, $this->source);
            })())) {
                // line 71
                echo '          <span class="visually-hidden">, selected</span>
        ';
            }
            // line 73
            echo '      ';
            $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 53
            echo twig_spaceless($___internal_parse_0_);
            echo craft\helpers\Html::tag((((            // line 34
                (isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                    throw new RuntimeError('Variable "type" does not exist.', 34, $this->source);
                })()) == 'button')) ? ('button') : ('a')), ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' =>             // line 35
                (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                    throw new RuntimeError('Variable "id" does not exist.', 35, $this->source);
                })()), 'class' => twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['menu-item' => true, 'sel' =>             // line 38
                (isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                    throw new RuntimeError('Variable "selected" does not exist.', 38, $this->source);
                })()), 'error' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 39
                    ($context['item'] ?? null), 'destructive', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'destructive', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'destructive', [])) : (false)), 'formsubmit' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 40
                        ($context['item'] ?? null), 'action', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [])) : (false)), ])), 'href' => (((            // line 42
                            (isset($context['type']) || array_key_exists('type', $context) ? $context['type'] : (function () {
                                throw new RuntimeError('Variable "type" does not exist.', 42, $this->source);
                            })()) == 'button')) ? (null) : (craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                                throw new RuntimeError('Variable "item" does not exist.', 42, $this->source);
                            })()), 'url', [])))), 'data' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['destructive' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 44
                                ($context['item'] ?? null), 'destructive', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'destructive', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'destructive', [])) : (null)), 'action' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 45
                                    ($context['item'] ?? null), 'action', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [])) : (null)), 'params' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 46
                                        ($context['item'] ?? null), 'params', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'params', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'params', [])) : (null)), 'confirm' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 47
                                            ($context['item'] ?? null), 'confirm', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'confirm', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'confirm', [])) : (null)), 'redirect' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 48
                                                ($context['item'] ?? null), 'redirect', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'redirect', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'redirect', [])) : (false))) ? ($this->env->getFilter('hash')->getCallable()(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                                                    throw new RuntimeError('Variable "item" does not exist.', 48, $this->source);
                                                })()), 'redirect', []))) : (null)), 'require-elevated-session' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 49
                                                    ($context['item'] ?? null), 'requireElevatedSession', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'requireElevatedSession', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'requireElevatedSession', [])) : (false)), 'form' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 50
                                                        ($context['item'] ?? null), 'action', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'action', [])) : (false))) ? ('false') : (null))]), ], (((craft\helpers\Template::attribute($this->env, $this->source,             // line 52
                                                            ($context['item'] ?? null), 'attributes', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'attributes', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'attributes', [])) : ([])), true));
            // line 75
            echo '  ';
            echo craft\helpers\Html::tag('li', ob_get_clean(), $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['hidden' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 30
                ($context['item'] ?? null), 'hidden', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'hidden', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'hidden', [])) : (false))]))], (((craft\helpers\Template::attribute($this->env, $this->source,             // line 32
                    ($context['item'] ?? null), 'liAttributes', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'liAttributes', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'liAttributes', [])) : ([]))));
            // line 76
            echo '  ';
            ob_start();
            // line 77
            echo "    \$('#";
            echo twig_escape_filter($this->env, $this->env->getFilter('namespaceInputId')->getCallable()((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 77, $this->source);
            })())), 'html', null, true);
            echo "').on('activate', () => {
      \$('#";
            // line 78
            echo twig_escape_filter($this->env, $this->env->getFilter('namespaceInputId')->getCallable()((isset($context['menuId']) || array_key_exists('menuId', $context) ? $context['menuId'] : (function () {
                throw new RuntimeError('Variable "menuId" does not exist.', 78, $this->source);
            })())), 'html', null, true);
            echo "').data('disclosureMenu').hide();
    });
  ";
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
            craft\helpers\Template::endProfile('macro', 'item');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_includes/disclosuremenu.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [449 => 78,  444 => 77,  441 => 76,  439 => 32,  438 => 30,  436 => 75,  434 => 52,  433 => 50,  432 => 49,  431 => 48,  430 => 47,  429 => 46,  428 => 45,  427 => 44,  426 => 42,  425 => 40,  424 => 39,  423 => 38,  422 => 35,  421 => 34,  419 => 53,  416 => 73,  412 => 71,  409 => 70,  403 => 68,  401 => 67,  396 => 66,  393 => 64,  390 => 63,  386 => 61,  384 => 60,  383 => 58,  381 => 55,  378 => 54,  376 => 53,  373 => 34,  370 => 33,  367 => 28,  364 => 27,  361 => 26,  346 => 25,  336 => 22,  322 => 21,  311 => 17,  308 => 15,  306 => 14,  304 => 13,  302 => 12,  300 => 11,  298 => 10,  296 => 9,  294 => 8,  280 => 7,  275 => 174,  270 => 173,  264 => 172,  258 => 170,  255 => 169,  252 => 168,  246 => 166,  240 => 164,  237 => 163,  234 => 162,  231 => 161,  228 => 160,  226 => 140,  224 => 159,  222 => 154,  221 => 152,  219 => 158,  210 => 156,  205 => 155,  202 => 150,  199 => 149,  197 => 146,  196 => 144,  191 => 147,  188 => 144,  185 => 143,  182 => 137,  178 => 135,  176 => 134,  173 => 133,  170 => 132,  168 => 131,  163 => 130,  160 => 129,  157 => 128,  154 => 127,  151 => 126,  146 => 125,  143 => 124,  138 => 123,  133 => 121,  132 => 120,  129 => 123,  127 => 119,  124 => 118,  121 => 100,  120 => 95,  119 => 94,  117 => 101,  114 => 115,  108 => 112,  105 => 111,  103 => 110,  100 => 109,  98 => 108,  97 => 107,  95 => 105,  91 => 103,  88 => 102,  86 => 101,  84 => 90,  81 => 89,  75 => 87,  73 => 86,  68 => 85,  65 => 84,  63 => 83,  60 => 82,  57 => 24,  54 => 20,  51 => 6,  49 => 5,  46 => 4,  44 => 3,  42 => 2,  40 => 1];
    }

    public function getSourceContext()
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
        <span class=\"menu-item-label\">{{ item.label ?? item.html|raw }}</span>
        {% if item.description is defined %}
          <div class=\"menu-item-description smalltext light\">{{ item.description }}</div>
        {% endif %}
        {% if selected %}
          <span class=\"visually-hidden\">, selected</span>
        {% endif %}
      {% endapply -%}
    {% endtag %}
  {% endtag %}
  {% js %}
    \$('#{{ id|namespaceInputId }}').on('activate', () => {
      \$('#{{ menuId|namespaceInputId }}').data('disclosureMenu').hide();
    });
  {% endjs %}
{% endmacro %}

{% if withButton %}
  {% if html ?? false %}
    {{ html | raw }}
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
              } | merge(item.headingAttributes ??{}, recursive=true) %}
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
", '_includes/disclosuremenu.twig', '/Users/brianhanson/Development/craft5/src/templates/_includes/disclosuremenu.twig');
    }
}
