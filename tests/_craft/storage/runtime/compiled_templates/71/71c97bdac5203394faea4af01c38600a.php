<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _elements/sources */
class __TwigTemplate_7043bae4e353a5b353ab593a2ef86dbf extends Template
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
        craft\helpers\Template::beginProfile('template', '_elements/sources');
        // line 1
        $___internal_parse_0_ = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            // line 2
            $context['keyPrefix'] ??= '';
            // line 3
            $context['isTopLevel'] = ! (isset($context['keyPrefix']) || array_key_exists('keyPrefix', $context) ? $context['keyPrefix'] : (function () {
                throw new RuntimeError('Variable "keyPrefix" does not exist.', 3, $this->source);
            })());
            // line 4
            yield '
';
            // line 5
            if (((isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
                throw new RuntimeError('Variable "isTopLevel" does not exist.', 5, $this->source);
            })()) && (! array_key_exists('baseSortOptions', $context) || ! array_key_exists('tableColumns', $context)))) {
                // line 6
                yield '    ';
                $context['elementInstance'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 6, $this->source);
                })()), 'app', [], 'any', false, false, false, 6), 'elements', [], 'any', false, false, false, 6), 'createElement', [(isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                    throw new RuntimeError('Variable "elementType" does not exist.', 6, $this->source);
                })())], 'method', false, false, false, 6);
                // line 7
                yield '    ';
                $context['baseSortOptions'] ??= array_values($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
                    throw new RuntimeError('Variable "elementInstance" does not exist.', 7, $this->source);
                })()), 'sortOptions', [], 'method', false, false, false, 7), function ($__option__, $__key__) use ($context) {
                    $context['option'] = $__option__;
                    $context['key'] = $__key__;

                    return ['label' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 8
                        ($context['option'] ?? null), 'label', [], 'any', true, true, false, 8) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'label', [], 'any', false, false, false, 8) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'label', [], 'any', false, false, false, 8)) : ((isset($context['option']) || array_key_exists('option', $context) ? $context['option'] : (function () {
                            throw new RuntimeError('Variable "option" does not exist.', 8, $this->source);
                        })()))), 'attr' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 9
                            ($context['option'] ?? null), 'attribute', [], 'any', true, true, false, 9) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'attribute', [], 'any', false, false, false, 9) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'attribute', [], 'any', false, false, false, 9)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'orderBy', [], 'any', true, true, false, 9) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'orderBy', [], 'any', false, false, false, 9) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'orderBy', [], 'any', false, false, false, 9)) : ((isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                throw new RuntimeError('Variable "key" does not exist.', 9, $this->source);
                            })()))))), 'defaultDir' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 10
                                ($context['option'] ?? null), 'defaultDir', [], 'any', true, true, false, 10) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'defaultDir', [], 'any', false, false, false, 10) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'defaultDir', [], 'any', false, false, false, 10)) : ('asc'))];
                }));
                // line 12
                yield '    ';
                $context['tableColumns'] ??= craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 12, $this->source);
                })()), 'app', [], 'any', false, false, false, 12), 'elementSources', [], 'any', false, false, false, 12), 'getAvailableTableAttributes', [(isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                    throw new RuntimeError('Variable "elementType" does not exist.', 12, $this->source);
                })())], 'method', false, false, false, 12);
            }
            // line 14
            yield '
';
            // line 55
            yield '
';
            // line 88
            yield '
';
            // line 89
            $context['nestedUnderHeading'] = false;
            // line 90
            yield '
';
            // line 91
            ob_start();
            // line 94
            yield '    ';
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['sources']) || array_key_exists('sources', $context) ? $context['sources'] : (function () {
                throw new RuntimeError('Variable "sources" does not exist.', 94, $this->source);
            })()));
            $context['loop'] = [
                'parent' => $context['_parent'],
                'index0' => 0,
                'index' => 1,
                'first' => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = $length === 1;
            }
            foreach ($context['_seq'] as $context['_key'] => $context['source']) {
                // line 95
                yield '        ';
                if (((((craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'type', [], 'any', true, true, false, 95) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'type', [], 'any', false, false, false, 95) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'type', [], 'any', false, false, false, 95)) : (null)) == 'heading')) {
                    // line 96
                    yield '            ';
                    if ((isset($context['nestedUnderHeading']) || array_key_exists('nestedUnderHeading', $context) ? $context['nestedUnderHeading'] : (function () {
                        throw new RuntimeError('Variable "nestedUnderHeading" does not exist.', 96, $this->source);
                    })())) {
                        // line 97
                        yield '                    </ul>
                </li>
            ';
                    }
                    // line 100
                    yield '            <li class="heading">
                <span>';
                    // line 101
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'heading', [], 'any', false, false, false, 101), 'site'), 'html', null, true);
                    yield '</span>
                <ul>
            ';
                    // line 103
                    $context['nestedUnderHeading'] = true;
                    // line 104
                    yield '        ';
                } elseif (! ((((craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'sites', [], 'any', true, true, false, 104) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'sites', [], 'any', false, false, false, 104) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'sites', [], 'any', false, false, false, 104)) : (null)) === [])) {
                    // line 105
                    yield '            ';
                    $context['key'] = (((craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'keyPath', [], 'any', true, true, false, 105) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'keyPath', [], 'any', false, false, false, 105) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'keyPath', [], 'any', false, false, false, 105)) : (((isset($context['keyPrefix']) || array_key_exists('keyPrefix', $context) ? $context['keyPrefix'] : (function () {
                        throw new RuntimeError('Variable "keyPrefix" does not exist.', 105, $this->source);
                    })()).craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'key', [], 'any', false, false, false, 105))));
                    // line 106
                    yield '            ';
                    ob_start();
                    // line 111
                    yield '                ';
                    yield CoreExtension::callMacro($macros['_self'], 'macro_sourceLink', [(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                        throw new RuntimeError('Variable "key" does not exist.', 111, $this->source);
                    })()), $context['source'], (isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
                        throw new RuntimeError('Variable "isTopLevel" does not exist.', 111, $this->source);
                    })()), (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                        throw new RuntimeError('Variable "elementType" does not exist.', 111, $this->source);
                    })()), (($context['baseSortOptions']) ?? (null)), (($context['tableColumns']) ?? (null)), (($context['defaultTableColumns']) ?? (null))], 111, $context, $this->getSourceContext());
                    yield '
                ';
                    // line 112
                    if ((craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'nested', [], 'any', true, true, false, 112) && ! Twig\Extension\CoreExtension::testEmpty(craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'nested', [], 'any', false, false, false, 112)))) {
                        // line 113
                        yield '                    <button class="toggle" aria-expanded="false" aria-label="';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Show nested sources', 'app'), 'html', null, true);
                        yield '"></button>
                    ';
                        // line 114
                        yield from $this->loadTemplate('_elements/sources', '_elements/sources', 114)->unwrap()->yield(CoreExtension::merge($context, ['keyPrefix' => (                        // line 115
                            (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                throw new RuntimeError('Variable "key" does not exist.', 115, $this->source);
                            })()).'/'), 'sources' => craft\helpers\Template::attribute($this->env, $this->source,                         // line 116
                                $context['source'], 'nested', [], 'any', false, false, false, 116)]));
                        // line 118
                        yield '                ';
                    }
                    // line 119
                    yield '            ';
                    echo craft\helpers\Html::tag('li', ob_get_clean(), ['class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [(((((craft\helpers\Template::attribute($this->env, $this->source,                     // line 108
                        $context['source'], 'disabled', [], 'any', true, true, false, 108) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'disabled', [], 'any', false, false, false, 108) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'disabled', [], 'any', false, false, false, 108)) : (false))) ? ('hidden') : (null))])]);
                    // line 120
                    yield '        ';
                }
                // line 121
                yield '    ';
                $context['loop']['index0']++;
                $context['loop']['index']++;
                $context['loop']['first'] = false;
                if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                    $context['loop']['revindex0']--;
                    $context['loop']['revindex']--;
                    $context['loop']['last'] = $context['loop']['revindex0'] === 0;
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['source'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 122
            yield '    ';
            if ((isset($context['nestedUnderHeading']) || array_key_exists('nestedUnderHeading', $context) ? $context['nestedUnderHeading'] : (function () {
                throw new RuntimeError('Variable "nestedUnderHeading" does not exist.', 122, $this->source);
            })())) {
                // line 123
                yield '            </ul>
        </li>
    ';
            }
            echo craft\helpers\Html::tag('ul', ob_get_clean(), ['class' => ((            // line 92
                (isset($context['keyPrefix']) || array_key_exists('keyPrefix', $context) ? $context['keyPrefix'] : (function () {
                    throw new RuntimeError('Variable "keyPrefix" does not exist.', 92, $this->source);
                })())) ? ('nested') : (null))]);
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        yield Twig\Extension\CoreExtension::spaceless($___internal_parse_0_);
        craft\helpers\Template::endProfile('template', '_elements/sources');
        yield from [];
    }

    // line 15
    public function macro_sourceLink($__key__ = null, $__source__ = null, $__isTopLevel__ = null, $__elementType__ = null, $__baseSortOptions__ = null, $__tableColumns__ = null, $__defaultTableColumns__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'key' => $__key__,
            'source' => $__source__,
            'isTopLevel' => $__isTopLevel__,
            'elementType' => $__elementType__,
            'baseSortOptions' => $__baseSortOptions__,
            'tableColumns' => $__tableColumns__,
            'defaultTableColumns' => $__defaultTableColumns__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'sourceLink');
            // line 16
            yield '    ';
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['role' => 'button', 'tabindex' => '0', 'data' => $this->extensions['craft\web\twig\Extension']->mergeFilter(['key' =>             // line 20
(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
    throw new RuntimeError('Variable "key" does not exist.', 20, $this->source);
})()), 'label' => craft\helpers\Template::attribute($this->env, $this->source,             // line 21
    (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
        throw new RuntimeError('Variable "source" does not exist.', 21, $this->source);
    })()), 'label', [], 'any', false, false, false, 21), 'has-thumbs' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 22
        ($context['source'] ?? null), 'hasThumbs', [], 'any', true, true, false, 22) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'hasThumbs', [], 'any', false, false, false, 22) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'hasThumbs', [], 'any', false, false, false, 22)) : (false))) ? (true) : (false)), 'has-structure' => boolval((((craft\helpers\Template::attribute($this->env, $this->source,             // line 23
            ($context['source'] ?? null), 'structureId', [], 'any', true, true, false, 23) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'structureId', [], 'any', false, false, false, 23) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'structureId', [], 'any', false, false, false, 23)) : (null))), 'default-sort' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 24
                ($context['source'] ?? null), 'defaultSort', [], 'any', true, true, false, 24) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultSort', [], 'any', false, false, false, 24) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultSort', [], 'any', false, false, false, 24)) : (false)), 'sort-opts' => ((            // line 25
                    (isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
                        throw new RuntimeError('Variable "isTopLevel" does not exist.', 25, $this->source);
                    })())) ? ($this->extensions['craft\web\twig\Extension']->mergeFilter(            // line 26
                        (isset($context['baseSortOptions']) || array_key_exists('baseSortOptions', $context) ? $context['baseSortOptions'] : (function () {
                            throw new RuntimeError('Variable "baseSortOptions" does not exist.', 26, $this->source);
                        })()), array_values($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,             // line 27
                            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                throw new RuntimeError('Variable "craft" does not exist.', 27, $this->source);
                            })()), 'app', [], 'any', false, false, false, 27), 'elementSources', [], 'any', false, false, false, 27), 'getSourceSortOptions', [(isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                                throw new RuntimeError('Variable "elementType" does not exist.', 27, $this->source);
                            })()), (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                throw new RuntimeError('Variable "key" does not exist.', 27, $this->source);
                            })())], 'method', false, false, false, 27), function ($__option__) use ($context) {
                                $context['option'] = $__option__;

                                return ['label' => craft\helpers\Template::attribute($this->env, $this->source,             // line 28
                                    (isset($context['option']) || array_key_exists('option', $context) ? $context['option'] : (function () {
                                        throw new RuntimeError('Variable "option" does not exist.', 28, $this->source);
                                    })()), 'label', [], 'any', false, false, false, 28), 'attr' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 29
                                        ($context['option'] ?? null), 'attribute', [], 'any', true, true, false, 29) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'attribute', [], 'any', false, false, false, 29) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'attribute', [], 'any', false, false, false, 29)) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['option']) || array_key_exists('option', $context) ? $context['option'] : (function () {
                                            throw new RuntimeError('Variable "option" does not exist.', 29, $this->source);
                                        })()), 'orderBy', [], 'any', false, false, false, 29))), 'defaultDir' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 30
                                            ($context['option'] ?? null), 'defaultDir', [], 'any', true, true, false, 30) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'defaultDir', [], 'any', false, false, false, 30) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'defaultDir', [], 'any', false, false, false, 30)) : ('asc'))];
                            })))) : (false)), 'source-item' => true, 'table-col-opts' => ((            // line 34
                                (isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
                                    throw new RuntimeError('Variable "isTopLevel" does not exist.', 34, $this->source);
                                })())) ? (array_values($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, $this->extensions['craft\web\twig\Extension']->mergeFilter(            // line 35
                                    (isset($context['tableColumns']) || array_key_exists('tableColumns', $context) ? $context['tableColumns'] : (function () {
                                        throw new RuntimeError('Variable "tableColumns" does not exist.', 35, $this->source);
                                    })()), craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,             // line 36
                                        (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                            throw new RuntimeError('Variable "craft" does not exist.', 36, $this->source);
                                        })()), 'app', [], 'any', false, false, false, 36), 'elementSources', [], 'any', false, false, false, 36), 'getSourceTableAttributes', [(isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                                            throw new RuntimeError('Variable "elementType" does not exist.', 36, $this->source);
                                        })()), (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                            throw new RuntimeError('Variable "key" does not exist.', 36, $this->source);
                                        })())], 'method', false, false, false, 36)),             // line 37
                                    function ($__a__, $__key__) use ($context) {
                                        $context['a'] = $__a__;
                                        $context['key'] = $__key__;

                                        return $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['a']) || array_key_exists('a', $context) ? $context['a'] : (function () {
                                            throw new RuntimeError('Variable "a" does not exist.', 37, $this->source);
                                        })()), ['attr' => (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                            throw new RuntimeError('Variable "key" does not exist.', 37, $this->source);
                                        })())]);
                                    }))) : (false)), 'default-table-cols' => ((            // line 40
                                        (isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
                                            throw new RuntimeError('Variable "isTopLevel" does not exist.', 40, $this->source);
                                        })())) ? (array_values($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, ((            // line 41
                                            $context['defaultTableColumns']) ?? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                                throw new RuntimeError('Variable "craft" does not exist.', 41, $this->source);
                                            })()), 'app', [], 'any', false, false, false, 41), 'elementSources', [], 'any', false, false, false, 41), 'getTableAttributes', [(isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                                                throw new RuntimeError('Variable "elementType" does not exist.', 41, $this->source);
                                            })()), (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                                throw new RuntimeError('Variable "key" does not exist.', 41, $this->source);
                                            })())], 'method', false, false, false, 41))),             // line 42
                                            function ($__a__) use ($context) {
                                                $context['a'] = $__a__;

                                                return craft\helpers\Template::attribute($this->env, $this->source, (isset($context['a']) || array_key_exists('a', $context) ? $context['a'] : (function () {
                                                    throw new RuntimeError('Variable "a" does not exist.', 42, $this->source);
                                                })()), 0, [], 'array', false, false, false, 42);
                                            }),             // line 43
                                            function ($__a__) use ($context) {
                                                $context['a'] = $__a__;

                                                return (isset($context['a']) || array_key_exists('a', $context) ? $context['a'] : (function () {
                                                    throw new RuntimeError('Variable "a" does not exist.', 43, $this->source);
                                                })()) != 'title';
                                            }))) : (false)), 'default-source-path' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 46
                                                ($context['source'] ?? null), 'defaultSourcePath', [], 'any', true, true, false, 46) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultSourcePath', [], 'any', false, false, false, 46) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultSourcePath', [], 'any', false, false, false, 46)) : (false))) ? ($this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                                                    throw new RuntimeError('Variable "source" does not exist.', 46, $this->source);
                                                })()), 'defaultSourcePath', [], 'any', false, false, false, 46))) : (false)), 'sites' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 47
                                                    ($context['source'] ?? null), 'sites', [], 'any', true, true, false, 47) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'sites', [], 'any', false, false, false, 47) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'sites', [], 'any', false, false, false, 47)) : (false))) ? (Twig\Extension\CoreExtension::join(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                                                        throw new RuntimeError('Variable "source" does not exist.', 47, $this->source);
                                                    })()), 'sites', [], 'any', false, false, false, 47), ',')) : (false)), 'criteria' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 48
                                                        ($context['source'] ?? null), 'criteria', [], 'any', true, true, false, 48) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'criteria', [], 'any', false, false, false, 48) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'criteria', [], 'any', false, false, false, 48)) : ([])), 'disabled' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 49
                                                            ($context['source'] ?? null), 'disabled', [], 'any', true, true, false, 49) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'disabled', [], 'any', false, false, false, 49) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'disabled', [], 'any', false, false, false, 49)) : (false)), 'default-filter' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 50
                                                                ($context['source'] ?? null), 'defaultFilter', [], 'any', true, true, false, 50) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultFilter', [], 'any', false, false, false, 50) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultFilter', [], 'any', false, false, false, 50)) : (false))], (((craft\helpers\Template::attribute($this->env, $this->source,             // line 51
                                                                    ($context['source'] ?? null), 'data', [], 'any', true, true, false, 51) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'data', [], 'any', false, false, false, 51) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'data', [], 'any', false, false, false, 51)) : ([]))), 'html' => CoreExtension::callMacro($macros['_self'], 'macro_sourceInnerHtml', [            // line 52
                                                                        (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                                                                            throw new RuntimeError('Variable "source" does not exist.', 52, $this->source);
                                                                        })())], 52, $context, $this->getSourceContext())]);
            // line 53
            yield '
';
            craft\helpers\Template::endProfile('macro', 'sourceLink');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    // line 56
    public function macro_sourceInnerHtml($__source__ = null, ...$__varargs__)
    {
        $context = [
            'source' => $__source__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'sourceInnerHtml');
            // line 57
            yield '    ';
            if (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'status', [], 'any', true, true, false, 57)) {
                // line 58
                yield '        <span class="status ';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 58, $this->source);
                })()), 'status', [], 'any', false, false, false, 58), 'html', null, true);
                yield '"></span>
    ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 59
                ($context['source'] ?? null), 'icon', [], 'any', true, true, false, 59)) {
                // line 60
                yield '        <span class="icon">
            ';
                // line 61
                yield ($this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 61, $this->source);
                })()), 'icon', [], 'any', false, false, false, 61), true, true)) ?: ((("<span data-icon='".craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 61, $this->source);
                })()), 'icon', [], 'any', false, false, false, 61))."'></span>"));
                yield '
        </span>
    ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 63
                ($context['source'] ?? null), 'iconMask', [], 'any', true, true, false, 63)) {
                // line 64
                yield '        <span class="icon icon-mask">
            ';
                // line 65
                yield ($this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 65, $this->source);
                })()), 'iconMask', [], 'any', false, false, false, 65), true, true)) ?: ((("<span data-icon='".craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 65, $this->source);
                })()), 'iconMask', [], 'any', false, false, false, 65))."'></span>"));
                yield '
        </span>
    ';
            }
            // line 68
            yield '    <span class="label">
        ';
            // line 69
            if (! (Twig\Extension\CoreExtension::trim(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                throw new RuntimeError('Variable "source" does not exist.', 69, $this->source);
            })()), 'label', [], 'any', false, false, false, 69)) === '')) {
                // line 70
                yield '            ';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'type', [], 'any', true, true, false, 70) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'type', [], 'any', false, false, false, 70) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'type', [], 'any', false, false, false, 70)) : (null)) == 'custom')) ? ($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 70, $this->source);
                })()), 'label', [], 'any', false, false, false, 70), 'site')) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 70, $this->source);
                })()), 'label', [], 'any', false, false, false, 70))), 'html', null, true);
                yield '
        ';
            } else {
                // line 72
                yield '            ';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('(blank)', 'app'), 'html', null, true);
                yield '
        ';
            }
            // line 74
            yield '    </span>
    ';
            // line 75
            if ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'badgeCount', [], 'any', true, true, false, 75) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'badgeCount', [], 'any', false, false, false, 75) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'badgeCount', [], 'any', false, false, false, 75)) : (false))) {
                // line 76
                yield '        <span class="badge" aria-hidden="true">';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->numberFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 76, $this->source);
                })()), 'badgeCount', [], 'any', false, false, false, 76), 0), 'html', null, true);
                yield '</span>
        ';
                // line 77
                yield $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'data' => ['notification' => true], 'text' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 82
                    ($context['source'] ?? null), 'badgeLabel', [], 'any', true, true, false, 82) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'badgeLabel', [], 'any', false, false, false, 82) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'badgeLabel', [], 'any', false, false, false, 82)) : ($this->extensions['craft\web\twig\Extension']->translateFilter('{num, number} {num, plural, =1{notification} other{notifications}}', 'app', ['num' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 83
                        (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                            throw new RuntimeError('Variable "source" does not exist.', 83, $this->source);
                        })()), 'badgeCount', [], 'any', false, false, false, 83)])))]);
                // line 85
                yield '
    ';
            }
            craft\helpers\Template::endProfile('macro', 'sourceInnerHtml');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_elements/sources';
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
        return [319 => 85,  317 => 83,  316 => 82,  315 => 77,  310 => 76,  308 => 75,  305 => 74,  299 => 72,  293 => 70,  291 => 69,  288 => 68,  282 => 65,  279 => 64,  277 => 63,  272 => 61,  269 => 60,  267 => 59,  262 => 58,  259 => 57,  246 => 56,  238 => 53,  236 => 52,  235 => 51,  234 => 50,  233 => 49,  232 => 48,  231 => 47,  230 => 46,  229 => 43,  228 => 42,  227 => 41,  226 => 40,  225 => 37,  224 => 36,  223 => 35,  222 => 34,  221 => 30,  220 => 29,  219 => 28,  218 => 27,  217 => 26,  216 => 25,  215 => 24,  214 => 23,  213 => 22,  212 => 21,  211 => 20,  209 => 16,  190 => 15,  184 => 1,  180 => 92,  175 => 123,  172 => 122,  158 => 121,  155 => 120,  153 => 108,  151 => 119,  148 => 118,  146 => 116,  145 => 115,  144 => 114,  139 => 113,  137 => 112,  132 => 111,  129 => 106,  126 => 105,  123 => 104,  121 => 103,  116 => 101,  113 => 100,  108 => 97,  105 => 96,  102 => 95,  84 => 94,  82 => 91,  79 => 90,  77 => 89,  74 => 88,  71 => 55,  68 => 14,  64 => 12,  62 => 10,  61 => 9,  60 => 8,  58 => 7,  55 => 6,  53 => 5,  50 => 4,  48 => 3,  46 => 2,  44 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% apply spaceless %}
{% set keyPrefix = keyPrefix ?? '' %}
{% set isTopLevel = not keyPrefix %}

{% if isTopLevel and (baseSortOptions is not defined or tableColumns is not defined) %}
    {% set elementInstance = craft.app.elements.createElement(elementType) %}
    {% set baseSortOptions = baseSortOptions ?? elementInstance.sortOptions()|map((option, key) => {
        label: option.label ?? option,
        attr: option.attribute ?? option.orderBy ?? key,
        defaultDir: option.defaultDir ?? 'asc',
    })|values %}
    {% set tableColumns = tableColumns ?? craft.app.elementSources.getAvailableTableAttributes(elementType) %}
{% endif %}

{% macro sourceLink(key, source, isTopLevel, elementType, baseSortOptions, tableColumns, defaultTableColumns) %}
    {{ tag('a', {
        role: 'button',
        tabindex: '0',
        data: {
            key: key,
            'label': source.label,
            'has-thumbs': (source.hasThumbs ?? false) ? true : false,
            'has-structure': (source.structureId ?? null)|boolean,
            'default-sort': source.defaultSort ?? false,
            'sort-opts': isTopLevel
                ? baseSortOptions
                    |merge(craft.app.elementSources.getSourceSortOptions(elementType, key)|map(option => {
                        label: option.label,
                        attr: option.attribute ?? option.orderBy,
                        defaultDir: option.defaultDir ?? 'asc'
                    })|values)
                : false,
            'source-item': true,
            'table-col-opts': isTopLevel
                ? tableColumns
                    |merge(craft.app.elementSources.getSourceTableAttributes(elementType, key))
                    |map((a, key) => a|merge({attr: key}))
                    |values
                : false,
            'default-table-cols': isTopLevel
                ? (defaultTableColumns ?? craft.app.elementSources.getTableAttributes(elementType, key))
                    |map(a => a[0])
                    |filter(a => a != 'title')
                    |values
                : false,
            'default-source-path': (source.defaultSourcePath ?? false) ? source.defaultSourcePath|json_encode : false,
            sites: (source.sites ?? false) ? source.sites|join(',') : false,
            criteria: source.criteria ?? {},
            disabled: source.disabled ?? false,
            'default-filter': source.defaultFilter ?? false,
        }|merge(source.data ?? {}),
        html: _self.sourceInnerHtml(source)
    }) }}
{% endmacro %}

{% macro sourceInnerHtml(source) %}
    {% if source.status is defined %}
        <span class=\"status {{ source.status }}\"></span>
    {% elseif source.icon is defined %}
        <span class=\"icon\">
            {{ (svg(source.icon, sanitize=true, namespace=true) ?: \"<span data-icon='#{source.icon}'></span>\")|raw }}
        </span>
    {% elseif source.iconMask is defined %}
        <span class=\"icon icon-mask\">
            {{ (svg(source.iconMask, sanitize=true, namespace=true) ?: \"<span data-icon='#{source.iconMask}'></span>\")|raw }}
        </span>
    {% endif %}
    <span class=\"label\">
        {% if source.label|trim is not same as('') %}
            {{ (source.type ?? null) == 'custom' ? source.label|t('site') : source.label }}
        {% else %}
            {{ '(blank)'|t('app') }}
        {% endif %}
    </span>
    {% if source.badgeCount ?? false %}
        <span class=\"badge\" aria-hidden=\"true\">{{ source.badgeCount|number(decimals=0) }}</span>
        {{ tag('span', {
            class: 'visually-hidden',
            data: {
                notification: true,
            },
            text: source.badgeLabel ?? '{num, number} {num, plural, =1{notification} other{notifications}}'|t('app', {
                num: source.badgeCount,
            }),
        }) }}
    {% endif %}
{% endmacro %}

{% set nestedUnderHeading = false %}

{% tag 'ul' with {
    class: keyPrefix ? 'nested' : null,
} %}
    {% for source in sources %}
        {% if (source.type ?? null) == 'heading' %}
            {% if nestedUnderHeading %}
                    </ul>
                </li>
            {% endif %}
            <li class=\"heading\">
                <span>{{ source.heading|t('site') }}</span>
                <ul>
            {% set nestedUnderHeading = true %}
        {% elseif (source.sites ?? null) is not same as([]) %}
            {% set key = source.keyPath ?? (keyPrefix ~ source.key) %}
            {% tag 'li' with {
                class: [
                    (source.disabled ?? false) ? 'hidden' : null,
                ]|filter,
            } %}
                {{ _self.sourceLink(key, source, isTopLevel, elementType, baseSortOptions ?? null, tableColumns ?? null, defaultTableColumns ?? null) }}
                {% if source.nested is defined and source.nested is not empty %}
                    <button class=\"toggle\" aria-expanded=\"false\" aria-label=\"{{ 'Show nested sources'|t('app') }}\"></button>
                    {% include \"_elements/sources\" with {
                        keyPrefix: key ~ '/',
                        sources: source.nested
                    } %}
                {% endif %}
            {% endtag %}
        {% endif %}
    {% endfor %}
    {% if nestedUnderHeading %}
            </ul>
        </li>
    {% endif %}
{% endtag %}
{% endapply %}
", '_elements/sources', '/tmp/packages/craft5/src/templates/_elements/sources.twig');
    }
}
