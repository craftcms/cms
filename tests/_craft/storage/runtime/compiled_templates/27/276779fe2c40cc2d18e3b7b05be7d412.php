<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _layouts/components/global-sidebar */
class __TwigTemplate_59d2967aee4ab8eb987360b2ff529a58 extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/global-sidebar');
        // line 1
        $macros['links'] = $this->macros['links'] = $this->loadTemplate('_includes/links', '_layouts/components/global-sidebar', 1)->unwrap();
        // line 2
        yield '
';
        // line 58
        yield '
<craft-global-sidebar>
    <header id="global-sidebar" class="global-sidebar">
        <div class="global-sidebar__header">
            ';
        // line 62
        yield from $this->loadTemplate('_layouts/components/system-info', '_layouts/components/global-sidebar', 62)->unwrap()->yield($context);
        // line 63
        yield '        </div>

        <div class="global-sidebar__nav">

            <nav id="nav" class="global-nav" aria-label="';
        // line 67
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Primary', 'app'), 'html', null, true);
        yield '">
                <ul>
                    ';
        // line 69
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 69, $this->source);
        })()), 'cp', [], 'any', false, false, false, 69), 'nav', [], 'method', false, false, false, 69));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 70
            yield '                        ';
            $context['itemAttributes'] = ['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 71
                $context['item'], 'id', [], 'any', false, false, false, 71), 'class' => [((craft\helpers\Template::attribute($this->env, $this->source,             // line 73
                    $context['item'], 'subnav', [], 'any', false, false, false, 73)) ? ('has-subnav') : (''))]];
            // line 76
            yield '                        <li ';
            yield craft\helpers\Html::renderTagAttributes((isset($context['itemAttributes']) || array_key_exists('itemAttributes', $context) ? $context['itemAttributes'] : (function () {
                throw new RuntimeError('Variable "itemAttributes" does not exist.', 76, $this->source);
            })()));
            yield '>
                            <div class="nav-item ';
            // line 77
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'sel', [], 'any', false, false, false, 77)) {
                yield 'sel';
            }
            yield '">
                                ';
            // line 78
            yield CoreExtension::callMacro($macros['_self'], 'macro_action', [$context['item']], 78, $context, $this->getSourceContext());
            yield '

                                ';
            // line 80
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'subnav', [], 'any', false, false, false, 80)) {
                // line 81
                yield '                                    <craft-disclosure class="nav-item__trigger">
                                        <button
                                            type="button"
                                            class="btn menubtn hairline"
                                            aria-controls="';
                // line 85
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'id', [], 'any', false, false, false, 85), 'html', null, true);
                yield '-subnav"
                                           aria-describedby="';
                // line 86
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'id', [], 'any', false, false, false, 86), 'html', null, true);
                yield '-link" aria-expanded="';
                yield (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'sel', [], 'any', false, false, false, 86)) ? ('true') : ('false');
                yield '"
                                        >
                                            <span class="visually-hidden">';
                // line 88
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Open subnavigation', 'app'), 'html', null, true);
                yield '</span>
                                        </button>
                                    </craft-disclosure>
                                ';
            }
            // line 92
            yield '                            </div>

                            ';
            // line 94
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'subnav', [], 'any', false, false, false, 94)) {
                // line 95
                yield '                                <ul class="nav-item__subnav ';
                yield (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'sel', [], 'any', false, false, false, 95)) ? ('is-open') : ('');
                yield '" id="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'id', [], 'any', false, false, false, 95), 'html', null, true);
                yield '-subnav">
                                    ';
                // line 96
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'subnav', [], 'any', false, false, false, 96));
                foreach ($context['_seq'] as $context['itemId'] => $context['item']) {
                    // line 97
                    yield '                                        ';
                    $context['itemIsSelected'] = (array_key_exists('selectedSubnavItem', $context) && ((isset($context['selectedSubnavItem']) || array_key_exists('selectedSubnavItem', $context) ? $context['selectedSubnavItem'] : (function () {
                        throw new RuntimeError('Variable "selectedSubnavItem" does not exist.', 97, $this->source);
                    })()) == $context['itemId']));
                    // line 99
                    yield '<li>
                                            <div class="nav-item nav-item--sub ';
                    // line 100
                    if ((isset($context['itemIsSelected']) || array_key_exists('itemIsSelected', $context) ? $context['itemIsSelected'] : (function () {
                        throw new RuntimeError('Variable "itemIsSelected" does not exist.', 100, $this->source);
                    })())) {
                        yield 'sel';
                    }
                    yield '">
                                            ';
                    // line 101
                    yield CoreExtension::callMacro($macros['_self'], 'macro_action', [$this->extensions['craft\web\twig\Extension']->mergeFilter($context['item'], ['linkAttributes' => ['class' => ['sidebar-action--sub'], 'aria' => ['current' => ((                    // line 105
                        (isset($context['itemIsSelected']) || array_key_exists('itemIsSelected', $context) ? $context['itemIsSelected'] : (function () {
                            throw new RuntimeError('Variable "itemIsSelected" does not exist.', 105, $this->source);
                        })())) ? ('page') : (false))]]]),                     // line 108
                        (isset($context['itemIsSelected']) || array_key_exists('itemIsSelected', $context) ? $context['itemIsSelected'] : (function () {
                            throw new RuntimeError('Variable "itemIsSelected" does not exist.', 108, $this->source);
                        })()), false], 101, $context, $this->getSourceContext());
                    yield '
                                            </div>
                                        </li>
                                    ';
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['itemId'], $context['item'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 112
                yield '                                </ul>
                            ';
            }
            // line 114
            yield '                        </li>
                    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 116
        yield '                </ul>
            </nav>
        </div>

        <div class="global-sidebar__footer">
            <div class="sidebar-actions">
                ';
        // line 122
        $context['toggleContent'] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            // line 123
            yield '                    <span class="sidebar-action__prefix">
                        <span class="nav-icon" aria-hidden="true" id="sidebar-toggle-icon">
                            ';
            // line 125
            if ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 125, $this->source);
            })()), 'app', [], 'any', false, false, false, 125), 'locale', [], 'any', false, false, false, 125), 'getOrientation', [], 'method', false, false, false, 125) == 'rtl')) {
                // line 126
                yield '                                ';
                yield craft\helpers\Cp::iconSvg('angle-left');
                yield '
                            ';
            } else {
                // line 128
                yield '                                ';
                yield craft\helpers\Cp::iconSvg('angle-right');
                yield '
                            ';
            }
            // line 130
            yield '                        </span>
                    </span>
                    <span class="sidebar-action__label">
                        <span class="label">';
            // line 133
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Toggle sidebar', 'app'), 'html', null, true);
            yield '</span>
                    </span>
                ';
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 136
        yield '
                ';
        // line 137
        yield from $this->loadTemplate('_includes/disclosure-toggle', '_layouts/components/global-sidebar', 137)->unwrap()->yield(CoreExtension::merge($context, ['id' => 'sidebar-trigger', 'controls' => 'global-sidebar', 'expanded' => (((        // line 140
            (isset($context['sidebarState']) || array_key_exists('sidebarState', $context) ? $context['sidebarState'] : (function () {
                throw new RuntimeError('Variable "sidebarState" does not exist.', 140, $this->source);
            })()) == 'expanded')) ? ('true') : ('false')), 'content' =>         // line 141
                        (isset($context['toggleContent']) || array_key_exists('toggleContent', $context) ? $context['toggleContent'] : (function () {
                            throw new RuntimeError('Variable "toggleContent" does not exist.', 141, $this->source);
                        })()), 'attributes' => ['class' => 'sidebar-action']]));
        // line 146
        yield '            </div>

            ';
        // line 148
        if ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 148, $this->source);
        })()), 'admin', [], 'any', false, false, false, 148) && (isset($context['devMode']) || array_key_exists('devMode', $context) ? $context['devMode'] : (function () {
            throw new RuntimeError('Variable "devMode" does not exist.', 148, $this->source);
        })()))) {
            // line 149
            yield '                ';
            $context['devModeText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Craft CMS is running in Dev Mode.', 'app');
            // line 150
            yield '                <div id="devmode">
                    ';
            // line 151
            ob_start();
            // line 154
            yield '                        ';
            yield isset($context['devModeText']) || array_key_exists('devModeText', $context) ? $context['devModeText'] : (function () {
                throw new RuntimeError('Variable "devModeText" does not exist.', 154, $this->source);
            })();
            yield '
                    ';
            echo craft\helpers\Html::tag('span', ob_get_clean(), ['class' => 'visually-hidden']);
            // line 156
            yield '                </div>
            ';
        }
        // line 158
        yield '        </div>
    </header>
</craft-global-sidebar>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/global-sidebar');
        yield from [];
    }

    // line 3
    public function macro_action($__item__ = null, $__isSelected__ = null, $__showIcon__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'item' => $__item__,
            'isSelected' => $__isSelected__,
            'showIcon' => $__showIcon__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'action');
            // line 4
            yield '    ';
            $context['showIcon'] ??= true;
            // line 5
            yield '    ';
            $context['selected'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'sel', [], 'any', true, true, false, 5) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'sel', [], 'any', false, false, false, 5) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'sel', [], 'any', false, false, false, 5)) : ((($context['isSelected']) ?? (false))));
            // line 6
            yield '    ';
            $context['badgeCount'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'badgeCount', [], 'any', true, true, false, 6) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'badgeCount', [], 'any', false, false, false, 6) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'badgeCount', [], 'any', false, false, false, 6)) : (false));
            // line 7
            yield '    ';
            $context['external'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'external', [], 'any', true, true, false, 7) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'external', [], 'any', false, false, false, 7) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'external', [], 'any', false, false, false, 7)) : (false));
            // line 8
            yield '
    ';
            // line 9
            $context['linkAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 10
                ($context['item'] ?? null), 'id', [], 'any', true, true, false, 10) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'id', [], 'any', false, false, false, 10) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'id', [], 'any', false, false, false, 10)) : (null))) ? ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 10, $this->source);
                })()), 'id', [], 'any', false, false, false, 10).'-link')) : (null)), 'href' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 11
                    ($context['item'] ?? null), 'url', [], 'any', true, true, false, 11) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'url', [], 'any', false, false, false, 11) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'url', [], 'any', false, false, false, 11)) : (false))) ? (craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 11, $this->source);
                    })()), 'url', [], 'any', false, false, false, 11))) : (null)), 'class' => ['sidebar-action', ((            // line 14
                        (isset($context['external']) || array_key_exists('external', $context) ? $context['external'] : (function () {
                            throw new RuntimeError('Variable "external" does not exist.', 14, $this->source);
                        })())) ? ('external') : ('')), ((            // line 15
                            (isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                                throw new RuntimeError('Variable "selected" does not exist.', 15, $this->source);
                            })())) ? ('sel') : (''))], 'target' => ((            // line 17
                                (isset($context['external']) || array_key_exists('external', $context) ? $context['external'] : (function () {
                                    throw new RuntimeError('Variable "external" does not exist.', 17, $this->source);
                                })())) ? ('_blank') : (''))], (((craft\helpers\Template::attribute($this->env, $this->source,             // line 18
                                    ($context['item'] ?? null), 'linkAttributes', [], 'any', true, true, false, 18) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'linkAttributes', [], 'any', false, false, false, 18) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'linkAttributes', [], 'any', false, false, false, 18)) : ([])), true);
            // line 19
            yield '
    ';
            // line 20
            ob_start();
            // line 21
            yield '        <span class="sidebar-action__prefix">
            ';
            // line 22
            if ((isset($context['showIcon']) || array_key_exists('showIcon', $context) ? $context['showIcon'] : (function () {
                throw new RuntimeError('Variable "showIcon" does not exist.', 22, $this->source);
            })())) {
                // line 23
                yield '                <span class="nav-icon" aria-hidden="true">';
                // line 24
                if (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'icon', [], 'any', true, true, false, 24)) {
                    // line 25
                    yield craft\helpers\Cp::iconSvg(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 25, $this->source);
                    })()), 'icon', [], 'any', false, false, false, 25));
                } elseif (craft\helpers\Template::attribute($this->env, $this->source,                 // line 26
                    ($context['item'] ?? null), 'fontIcon', [], 'any', true, true, false, 26)) {
                    // line 27
                    yield '<span data-icon="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 27, $this->source);
                    })()), 'fontIcon', [], 'any', false, false, false, 27), 'html', null, true);
                    yield '"></span>';
                } else {
                    // line 29
                    yield from $this->loadTemplate('_includes/fallback-icon.svg.twig', '_layouts/components/global-sidebar', 29)->unwrap()->yield(CoreExtension::merge($context, ['label' => craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 29, $this->source);
                    })()), 'label', [], 'any', false, false, false, 29)]));
                }
                // line 31
                yield '</span>
            ';
            }
            // line 33
            yield '        </span>

        <span class="sidebar-action__label">
            <span class="label">';
            // line 36
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                throw new RuntimeError('Variable "item" does not exist.', 36, $this->source);
            })()), 'label', [], 'any', false, false, false, 36), 'html', null, true);
            yield '</span>
            ';
            // line 37
            if ((isset($context['external']) || array_key_exists('external', $context) ? $context['external'] : (function () {
                throw new RuntimeError('Variable "external" does not exist.', 37, $this->source);
            })())) {
                // line 38
                yield '                ';
                yield CoreExtension::callMacro($macros['links'], 'macro_externalLinkIcon', [], 38, $context, $this->getSourceContext());
                yield '
            ';
            }
            // line 40
            yield '        </span>';
            // line 42
            if ((isset($context['badgeCount']) || array_key_exists('badgeCount', $context) ? $context['badgeCount'] : (function () {
                throw new RuntimeError('Variable "badgeCount" does not exist.', 42, $this->source);
            })())) {
                // line 43
                yield '<span class="sidebar-action__badge">
                <span class="badge" aria-hidden="true">';
                // line 44
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->numberFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 44, $this->source);
                })()), 'badgeCount', [], 'any', false, false, false, 44), 0), 'html', null, true);
                yield '</span>
                ';
                // line 45
                yield $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'data' => ['notification' => true], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('{num, number} {num, plural, =1{notification} other{notifications}}', 'app', ['num' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 51
                    (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 51, $this->source);
                    })()), 'badgeCount', [], 'any', false, false, false, 51)])]);
                // line 53
                yield '
            </span>';
            }
            echo craft\helpers\Html::tag('a', ob_get_clean(),             // line 20
                (isset($context['linkAttributes']) || array_key_exists('linkAttributes', $context) ? $context['linkAttributes'] : (function () {
                    throw new RuntimeError('Variable "linkAttributes" does not exist.', 20, $this->source);
                })()));
            craft\helpers\Template::endProfile('macro', 'action');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/global-sidebar';
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
        return [354 => 20,  350 => 53,  348 => 51,  347 => 45,  343 => 44,  340 => 43,  338 => 42,  336 => 40,  330 => 38,  328 => 37,  324 => 36,  319 => 33,  315 => 31,  312 => 29,  307 => 27,  305 => 26,  303 => 25,  301 => 24,  299 => 23,  297 => 22,  294 => 21,  292 => 20,  289 => 19,  287 => 18,  286 => 17,  285 => 15,  284 => 14,  283 => 11,  282 => 10,  281 => 9,  278 => 8,  275 => 7,  272 => 6,  269 => 5,  266 => 4,  251 => 3,  242 => 158,  238 => 156,  232 => 154,  230 => 151,  227 => 150,  224 => 149,  222 => 148,  218 => 146,  216 => 141,  215 => 140,  214 => 137,  211 => 136,  204 => 133,  199 => 130,  193 => 128,  187 => 126,  185 => 125,  181 => 123,  179 => 122,  171 => 116,  164 => 114,  160 => 112,  150 => 108,  149 => 105,  148 => 101,  142 => 100,  139 => 99,  136 => 97,  132 => 96,  125 => 95,  123 => 94,  119 => 92,  112 => 88,  105 => 86,  101 => 85,  95 => 81,  93 => 80,  88 => 78,  82 => 77,  77 => 76,  75 => 73,  74 => 71,  72 => 70,  68 => 69,  63 => 67,  57 => 63,  55 => 62,  49 => 58,  46 => 2,  44 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/links\" as links %}

{% macro action(item, isSelected, showIcon) %}
    {% set showIcon = showIcon ?? true %}
    {% set selected = item.sel ?? isSelected ?? false %}
    {% set badgeCount = item.badgeCount ?? false %}
    {% set external = item.external ?? false %}

    {% set linkAttributes = {
        id: (item.id ?? null) ? \"#{item.id}-link\" : null,
        href: (item.url ?? false) ? url(item.url) : null,
        class: [
            'sidebar-action',
            (external ? 'external'),
            (selected ? 'sel'),
        ],
        target: external ? '_blank',
    }|merge(item.linkAttributes ?? {}, recursive=true) %}

    {% tag 'a' with linkAttributes %}
        <span class=\"sidebar-action__prefix\">
            {% if showIcon %}
                <span class=\"nav-icon\" aria-hidden=\"true\">
                    {%- if item.icon is defined -%}
                        {{ iconSvg(item.icon) }}
                    {%- elseif item.fontIcon is defined -%}
                        <span data-icon=\"{{ item.fontIcon }}\"></span>
                    {%- else -%}
                        {% include \"_includes/fallback-icon.svg.twig\" with { label: item.label } %}
                    {%- endif -%}
                </span>
            {% endif %}
        </span>

        <span class=\"sidebar-action__label\">
            <span class=\"label\">{{ item.label }}</span>
            {% if external %}
                {{ links.externalLinkIcon() }}
            {% endif %}
        </span>

        {%- if badgeCount -%}
            <span class=\"sidebar-action__badge\">
                <span class=\"badge\" aria-hidden=\"true\">{{ item.badgeCount|number(decimals=0) }}</span>
                {{ tag('span', {
                    class: 'visually-hidden',
                    data: {
                        notification: true,
                    },
                    text: '{num, number} {num, plural, =1{notification} other{notifications}}'|t('app', {
                        num: item.badgeCount,
                    }),
                }) }}
            </span>
        {%- endif -%}
    {% endtag %}
{% endmacro %}

<craft-global-sidebar>
    <header id=\"global-sidebar\" class=\"global-sidebar\">
        <div class=\"global-sidebar__header\">
            {% include '_layouts/components/system-info' %}
        </div>

        <div class=\"global-sidebar__nav\">

            <nav id=\"nav\" class=\"global-nav\" aria-label=\"{{ 'Primary'|t('app') }}\">
                <ul>
                    {% for item in craft.cp.nav() %}
                        {% set itemAttributes = {
                            id: item.id,
                            class: [
                                item.subnav ? 'has-subnav'
                            ],
                        } %}
                        <li {{ attr(itemAttributes) }}>
                            <div class=\"nav-item {% if item.sel %}sel{% endif %}\">
                                {{ _self.action(item) }}

                                {% if item.subnav %}
                                    <craft-disclosure class=\"nav-item__trigger\">
                                        <button
                                            type=\"button\"
                                            class=\"btn menubtn hairline\"
                                            aria-controls=\"{{ item.id }}-subnav\"
                                           aria-describedby=\"{{ item.id }}-link\" aria-expanded=\"{{ item.sel ?  'true' : 'false' }}\"
                                        >
                                            <span class=\"visually-hidden\">{{ 'Open subnavigation' |t('app') }}</span>
                                        </button>
                                    </craft-disclosure>
                                {% endif %}
                            </div>

                            {% if item.subnav %}
                                <ul class=\"nav-item__subnav {{ item.sel ? 'is-open' : '' -}}\" id=\"{{ item.id }}-subnav\">
                                    {% for itemId, item in item.subnav %}
                                        {% set itemIsSelected = selectedSubnavItem is defined and selectedSubnavItem == itemId -%}

                                        <li>
                                            <div class=\"nav-item nav-item--sub {% if itemIsSelected %}sel{% endif %}\">
                                            {{ _self.action(item|merge({
                                                linkAttributes:  {
                                                    class: ['sidebar-action--sub'],
                                                    aria: {
                                                        current: itemIsSelected ? 'page' : false,
                                                    },
                                                }
                                            }), itemIsSelected, false, ) }}
                                            </div>
                                        </li>
                                    {% endfor %}
                                </ul>
                            {% endif %}
                        </li>
                    {% endfor %}
                </ul>
            </nav>
        </div>

        <div class=\"global-sidebar__footer\">
            <div class=\"sidebar-actions\">
                {% set toggleContent %}
                    <span class=\"sidebar-action__prefix\">
                        <span class=\"nav-icon\" aria-hidden=\"true\" id=\"sidebar-toggle-icon\">
                            {% if craft.app.locale.getOrientation() == 'rtl' %}
                                {{ iconSvg('angle-left') }}
                            {% else %}
                                {{ iconSvg('angle-right') }}
                            {% endif %}
                        </span>
                    </span>
                    <span class=\"sidebar-action__label\">
                        <span class=\"label\">{{ 'Toggle sidebar'|t('app') }}</span>
                    </span>
                {% endset %}

                {% include '_includes/disclosure-toggle' with {
                    id: 'sidebar-trigger',
                    controls: 'global-sidebar',
                    expanded: sidebarState == 'expanded' ? 'true' : 'false',
                    content: toggleContent,
                    attributes: {
                        class: 'sidebar-action',
                    },
                } %}
            </div>

            {% if currentUser.admin and devMode %}
                {% set devModeText = 'Craft CMS is running in Dev Mode.'|t('app') %}
                <div id=\"devmode\">
                    {% tag 'span' with {
                        class: 'visually-hidden',
                    } %}
                        {{ devModeText|raw }}
                    {% endtag %}
                </div>
            {% endif %}
        </div>
    </header>
</craft-global-sidebar>
", '_layouts/components/global-sidebar', '/tmp/packages/craft5/src/templates/_layouts/components/global-sidebar.twig');
    }
}
