<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _elements/sources */
class __TwigTemplate_fdd1798e62302092bf5c4206b5bfa385 extends Template
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
        craft\helpers\Template::beginProfile('template', '_elements/sources');
        // line 1
        ob_start();
        // line 2
        $context['keyPrefix'] ??= '';
        // line 3
        $context['isTopLevel'] = ! (isset($context['keyPrefix']) || array_key_exists('keyPrefix', $context) ? $context['keyPrefix'] : (function () {
            throw new RuntimeError('Variable "keyPrefix" does not exist.', 3, $this->source);
        })());
        // line 4
        echo '
';
        // line 5
        if (((isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
            throw new RuntimeError('Variable "isTopLevel" does not exist.', 5, $this->source);
        })()) && (! array_key_exists('baseSortOptions', $context) || ! array_key_exists('tableColumns', $context)))) {
            // line 6
            echo '    ';
            $context['elementInstance'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 6, $this->source);
            })()), 'app', []), 'elements', []), 'createElement', [0 => (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                throw new RuntimeError('Variable "elementType" does not exist.', 6, $this->source);
            })())], 'method');
            // line 7
            echo '    ';
            $context['baseSortOptions'] ??= array_values($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
                throw new RuntimeError('Variable "elementInstance" does not exist.', 7, $this->source);
            })()), 'sortOptions', [], 'method'), function ($__option__, $__key__) use ($context) {
                $context['option'] = $__option__;
                $context['key'] = $__key__;

                return ['label' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 8
                    ($context['option'] ?? null), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'label', [])) : ((isset($context['option']) || array_key_exists('option', $context) ? $context['option'] : (function () {
                        throw new RuntimeError('Variable "option" does not exist.', 8, $this->source);
                    })()))), 'attr' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 9
                        ($context['option'] ?? null), 'attribute', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'attribute', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'attribute', [])) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'orderBy', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'orderBy', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'orderBy', [])) : ((isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                            throw new RuntimeError('Variable "key" does not exist.', 9, $this->source);
                        })()))))), 'defaultDir' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 10
                            ($context['option'] ?? null), 'defaultDir', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'defaultDir', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'defaultDir', [])) : ('asc'))];
            }));
            // line 12
            echo '    ';
            $context['tableColumns'] ??= craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 12, $this->source);
            })()), 'app', []), 'elementSources', []), 'getAvailableTableAttributes', [0 => (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                throw new RuntimeError('Variable "elementType" does not exist.', 12, $this->source);
            })())], 'method');
        }
        // line 14
        echo '
';
        // line 55
        echo '
';
        // line 88
        echo '
';
        // line 89
        $context['nestedUnderHeading'] = false;
        // line 90
        echo '
';
        // line 91
        ob_start();
        // line 94
        echo '    ';
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['sources']) || array_key_exists('sources', $context) ? $context['sources'] : (function () {
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
            echo '        ';
            if (((((craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'type', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'type', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'type', [])) : (null)) == 'heading')) {
                // line 96
                echo '            ';
                if ((isset($context['nestedUnderHeading']) || array_key_exists('nestedUnderHeading', $context) ? $context['nestedUnderHeading'] : (function () {
                    throw new RuntimeError('Variable "nestedUnderHeading" does not exist.', 96, $this->source);
                })())) {
                    // line 97
                    echo '                    </ul>
                </li>
            ';
                }
                // line 100
                echo '            <li class="heading">
                <span>';
                // line 101
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'heading', []), 'site'), 'html', null, true);
                echo '</span>
                <ul>
            ';
                // line 103
                $context['nestedUnderHeading'] = true;
                // line 104
                echo '        ';
            } else {
                // line 105
                echo '            ';
                $context['key'] = (((craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'keyPath', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'keyPath', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'keyPath', [])) : (((isset($context['keyPrefix']) || array_key_exists('keyPrefix', $context) ? $context['keyPrefix'] : (function () {
                    throw new RuntimeError('Variable "keyPrefix" does not exist.', 105, $this->source);
                })()).craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'key', []))));
                // line 106
                echo '            ';
                ob_start();
                // line 111
                echo '                ';
                echo twig_call_macro($macros['_self'], 'macro_sourceLink', [(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                    throw new RuntimeError('Variable "key" does not exist.', 111, $this->source);
                })()), $context['source'], (isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
                    throw new RuntimeError('Variable "isTopLevel" does not exist.', 111, $this->source);
                })()), (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                    throw new RuntimeError('Variable "elementType" does not exist.', 111, $this->source);
                })()), (($context['baseSortOptions']) ?? (null)), (($context['tableColumns']) ?? (null)), (($context['defaultTableColumns']) ?? (null))], 111, $context, $this->getSourceContext());
                echo '
                ';
                // line 112
                if ((craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'nested', [], 'any', true, true) && ! twig_test_empty(craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'nested', [])))) {
                    // line 113
                    echo '                    <button class="toggle" aria-expanded="false" aria-label="';
                    echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Show nested sources', 'app'), 'html', null, true);
                    echo '"></button>
                    ';
                    // line 114
                    $this->loadTemplate('_elements/sources', '_elements/sources', 114)->display(twig_array_merge($context, ['keyPrefix' => (                    // line 115
                        (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                            throw new RuntimeError('Variable "key" does not exist.', 115, $this->source);
                        })()).'/'), 'sources' => craft\helpers\Template::attribute($this->env, $this->source,                     // line 116
                            $context['source'], 'nested', [])]));
                    // line 118
                    echo '                ';
                }
                // line 119
                echo '            ';
                echo craft\helpers\Html::tag('li', ob_get_clean(), ['class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => (((((craft\helpers\Template::attribute($this->env, $this->source,                 // line 108
                    $context['source'], 'disabled', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'disabled', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['source'], 'disabled', [])) : (false))) ? ('hidden') : (null))])]);
                // line 120
                echo '        ';
            }
            // line 121
            echo '    ';
            $context['loop']['index0']++;
            $context['loop']['index']++;
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                $context['loop']['revindex0']--;
                $context['loop']['revindex']--;
                $context['loop']['last'] = $context['loop']['revindex0'] === 0;
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['source'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 122
        echo '    ';
        if ((isset($context['nestedUnderHeading']) || array_key_exists('nestedUnderHeading', $context) ? $context['nestedUnderHeading'] : (function () {
            throw new RuntimeError('Variable "nestedUnderHeading" does not exist.', 122, $this->source);
        })())) {
            // line 123
            echo '            </ul>
        </li>
    ';
        }
        echo craft\helpers\Html::tag('ul', ob_get_clean(), ['class' => ((        // line 92
            (isset($context['keyPrefix']) || array_key_exists('keyPrefix', $context) ? $context['keyPrefix'] : (function () {
                throw new RuntimeError('Variable "keyPrefix" does not exist.', 92, $this->source);
            })())) ? ('nested') : (null))]);
        $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        echo twig_spaceless($___internal_parse_0_);
        craft\helpers\Template::endProfile('template', '_elements/sources');
    }

    // line 15
    public function macro_sourceLink($__key__ = null, $__source__ = null, $__isTopLevel__ = null, $__elementType__ = null, $__baseSortOptions__ = null, $__tableColumns__ = null, $__defaultTableColumns__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'key' => $__key__,
            'source' => $__source__,
            'isTopLevel' => $__isTopLevel__,
            'elementType' => $__elementType__,
            'baseSortOptions' => $__baseSortOptions__,
            'tableColumns' => $__tableColumns__,
            'defaultTableColumns' => $__defaultTableColumns__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'sourceLink');
            // line 16
            echo '    ';
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['role' => 'button', 'tabindex' => '0', 'data' => $this->extensions['craft\web\twig\Extension']->mergeFilter(['key' =>             // line 20
(isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
    throw new RuntimeError('Variable "key" does not exist.', 20, $this->source);
})()), 'label' => craft\helpers\Template::attribute($this->env, $this->source,             // line 21
    (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
        throw new RuntimeError('Variable "source" does not exist.', 21, $this->source);
    })()), 'label', []), 'has-thumbs' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 22
        ($context['source'] ?? null), 'hasThumbs', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'hasThumbs', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'hasThumbs', [])) : (false))) ? (true) : (false)), 'has-structure' => boolval((((craft\helpers\Template::attribute($this->env, $this->source,             // line 23
            ($context['source'] ?? null), 'structureId', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'structureId', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'structureId', [])) : (null))), 'default-sort' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 24
                ($context['source'] ?? null), 'defaultSort', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultSort', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultSort', [])) : (false)), 'sort-opts' => ((            // line 25
                    (isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
                        throw new RuntimeError('Variable "isTopLevel" does not exist.', 25, $this->source);
                    })())) ? ($this->extensions['craft\web\twig\Extension']->mergeFilter(            // line 26
                        (isset($context['baseSortOptions']) || array_key_exists('baseSortOptions', $context) ? $context['baseSortOptions'] : (function () {
                            throw new RuntimeError('Variable "baseSortOptions" does not exist.', 26, $this->source);
                        })()), array_values($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,             // line 27
                            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                throw new RuntimeError('Variable "craft" does not exist.', 27, $this->source);
                            })()), 'app', []), 'elementSources', []), 'getSourceSortOptions', [0 => (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                                throw new RuntimeError('Variable "elementType" does not exist.', 27, $this->source);
                            })()), 1 => (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                throw new RuntimeError('Variable "key" does not exist.', 27, $this->source);
                            })())], 'method'), function ($__option__) use ($context) {
                                $context['option'] = $__option__;

                                return ['label' => craft\helpers\Template::attribute($this->env, $this->source,             // line 28
                                    (isset($context['option']) || array_key_exists('option', $context) ? $context['option'] : (function () {
                                        throw new RuntimeError('Variable "option" does not exist.', 28, $this->source);
                                    })()), 'label', []), 'attr' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 29
                                        ($context['option'] ?? null), 'attribute', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'attribute', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'attribute', [])) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['option']) || array_key_exists('option', $context) ? $context['option'] : (function () {
                                            throw new RuntimeError('Variable "option" does not exist.', 29, $this->source);
                                        })()), 'orderBy', []))), 'defaultDir' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 30
                                            ($context['option'] ?? null), 'defaultDir', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'defaultDir', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['option'] ?? null), 'defaultDir', [])) : ('asc'))];
                            })))) : (false)), 'source-item' => true, 'table-col-opts' => ((            // line 34
                                (isset($context['isTopLevel']) || array_key_exists('isTopLevel', $context) ? $context['isTopLevel'] : (function () {
                                    throw new RuntimeError('Variable "isTopLevel" does not exist.', 34, $this->source);
                                })())) ? (array_values($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, $this->extensions['craft\web\twig\Extension']->mergeFilter(            // line 35
                                    (isset($context['tableColumns']) || array_key_exists('tableColumns', $context) ? $context['tableColumns'] : (function () {
                                        throw new RuntimeError('Variable "tableColumns" does not exist.', 35, $this->source);
                                    })()), craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,             // line 36
                                        (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                            throw new RuntimeError('Variable "craft" does not exist.', 36, $this->source);
                                        })()), 'app', []), 'elementSources', []), 'getSourceTableAttributes', [0 => (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                                            throw new RuntimeError('Variable "elementType" does not exist.', 36, $this->source);
                                        })()), 1 => (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                            throw new RuntimeError('Variable "key" does not exist.', 36, $this->source);
                                        })())], 'method')),             // line 37
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
                                            })()), 'app', []), 'elementSources', []), 'getTableAttributes', [0 => (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
                                                throw new RuntimeError('Variable "elementType" does not exist.', 41, $this->source);
                                            })()), 1 => (isset($context['key']) || array_key_exists('key', $context) ? $context['key'] : (function () {
                                                throw new RuntimeError('Variable "key" does not exist.', 41, $this->source);
                                            })())], 'method'))),             // line 42
                                            function ($__a__) use ($context) {
                                                $context['a'] = $__a__;

                                                return craft\helpers\Template::attribute($this->env, $this->source, (isset($context['a']) || array_key_exists('a', $context) ? $context['a'] : (function () {
                                                    throw new RuntimeError('Variable "a" does not exist.', 42, $this->source);
                                                })()), 0, [], 'array');
                                            }),             // line 43
                                            function ($__a__) use ($context) {
                                                $context['a'] = $__a__;

                                                return (isset($context['a']) || array_key_exists('a', $context) ? $context['a'] : (function () {
                                                    throw new RuntimeError('Variable "a" does not exist.', 43, $this->source);
                                                })()) != 'title';
                                            }))) : (false)), 'default-source-path' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 46
                                                ($context['source'] ?? null), 'defaultSourcePath', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultSourcePath', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultSourcePath', [])) : (false))) ? ($this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                                                    throw new RuntimeError('Variable "source" does not exist.', 46, $this->source);
                                                })()), 'defaultSourcePath', []))) : (false)), 'sites' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 47
                                                    ($context['source'] ?? null), 'sites', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'sites', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'sites', [])) : (false))) ? (twig_join_filter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                                                        throw new RuntimeError('Variable "source" does not exist.', 47, $this->source);
                                                    })()), 'sites', []), ',')) : (false)), 'criteria' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 48
                                                        ($context['source'] ?? null), 'criteria', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'criteria', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'criteria', [])) : ([])), 'disabled' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 49
                                                            ($context['source'] ?? null), 'disabled', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'disabled', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'disabled', [])) : (false)), 'default-filter' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 50
                                                                ($context['source'] ?? null), 'defaultFilter', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultFilter', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'defaultFilter', [])) : (false)), ], (((craft\helpers\Template::attribute($this->env, $this->source,             // line 51
                                                                    ($context['source'] ?? null), 'data', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'data', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'data', [])) : ([]))), 'html' => twig_call_macro($macros['_self'], 'macro_sourceInnerHtml', [            // line 52
                                                                        (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                                                                            throw new RuntimeError('Variable "source" does not exist.', 52, $this->source);
                                                                        })()), ], 52, $context, $this->getSourceContext())]);
            // line 53
            echo '
';
            craft\helpers\Template::endProfile('macro', 'sourceLink');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 56
    public function macro_sourceInnerHtml($__source__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'source' => $__source__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'sourceInnerHtml');
            // line 57
            echo '    ';
            if (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'status', [], 'any', true, true)) {
                // line 58
                echo '        <span class="status ';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 58, $this->source);
                })()), 'status', []), 'html', null, true);
                echo '"></span>
    ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 59
                ($context['source'] ?? null), 'icon', [], 'any', true, true)) {
                // line 60
                echo '        <span class="icon">
            ';
                // line 61
                echo $this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 61, $this->source);
                })()), 'icon', []), true, true) ?: ("<span data-icon='".craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 61, $this->source);
                })()), 'icon', []))."'></span>";
                echo '
        </span>
    ';
            } elseif (craft\helpers\Template::attribute($this->env, $this->source,             // line 63
                ($context['source'] ?? null), 'iconMask', [], 'any', true, true)) {
                // line 64
                echo '        <span class="icon icon-mask">
            ';
                // line 65
                echo $this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 65, $this->source);
                })()), 'iconMask', []), true, true) ?: ("<span data-icon='".craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 65, $this->source);
                })()), 'iconMask', []))."'></span>";
                echo '
        </span>
    ';
            }
            // line 68
            echo '    <span class="label">
        ';
            // line 69
            if (! (twig_trim_filter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                throw new RuntimeError('Variable "source" does not exist.', 69, $this->source);
            })()), 'label', [])) === '')) {
                // line 70
                echo '            ';
                echo twig_escape_filter($this->env, ((((((craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'type', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'type', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'type', [])) : (null)) == 'custom')) ? ($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 70, $this->source);
                })()), 'label', []), 'site')) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 70, $this->source);
                })()), 'label', []))), 'html', null, true);
                echo '
        ';
            } else {
                // line 72
                echo '            ';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('(blank)', 'app'), 'html', null, true);
                echo '
        ';
            }
            // line 74
            echo '    </span>
    ';
            // line 75
            if (craft\helpers\Template::attribute($this->env, $this->source, ($context['source'] ?? null), 'badgeCount', [], 'any', true, true)) {
                // line 76
                echo '        <span class="badge" aria-hidden="true">';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->numberFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                    throw new RuntimeError('Variable "source" does not exist.', 76, $this->source);
                })()), 'badgeCount', []), 0), 'html', null, true);
                echo '</span>
        ';
                // line 77
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'data' => ['notification' => true], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('{num, number} {num, plural, =1{notification} other{notifications}}', 'app', ['num' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 83
                    (isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                        throw new RuntimeError('Variable "source" does not exist.', 83, $this->source);
                    })()), 'badgeCount', [])])]);
                // line 85
                echo '
    ';
            }
            craft\helpers\Template::endProfile('macro', 'sourceInnerHtml');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_elements/sources';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [316 => 85,  314 => 83,  313 => 77,  308 => 76,  306 => 75,  303 => 74,  297 => 72,  291 => 70,  289 => 69,  286 => 68,  280 => 65,  277 => 64,  275 => 63,  270 => 61,  267 => 60,  265 => 59,  260 => 58,  257 => 57,  243 => 56,  232 => 53,  230 => 52,  229 => 51,  228 => 50,  227 => 49,  226 => 48,  225 => 47,  224 => 46,  223 => 43,  222 => 42,  221 => 41,  220 => 40,  219 => 37,  218 => 36,  217 => 35,  216 => 34,  215 => 30,  214 => 29,  213 => 28,  212 => 27,  211 => 26,  210 => 25,  209 => 24,  208 => 23,  207 => 22,  206 => 21,  205 => 20,  203 => 16,  183 => 15,  178 => 1,  175 => 92,  170 => 123,  167 => 122,  153 => 121,  150 => 120,  148 => 108,  146 => 119,  143 => 118,  141 => 116,  140 => 115,  139 => 114,  134 => 113,  132 => 112,  127 => 111,  124 => 106,  121 => 105,  118 => 104,  116 => 103,  111 => 101,  108 => 100,  103 => 97,  100 => 96,  97 => 95,  79 => 94,  77 => 91,  74 => 90,  72 => 89,  69 => 88,  66 => 55,  63 => 14,  59 => 12,  57 => 10,  56 => 9,  55 => 8,  53 => 7,  50 => 6,  48 => 5,  45 => 4,  43 => 3,  41 => 2,  39 => 1];
    }

    public function getSourceContext()
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
    {% if source.badgeCount is defined %}
        <span class=\"badge\" aria-hidden=\"true\">{{ source.badgeCount|number(decimals=0) }}</span>
        {{ tag('span', {
            class: 'visually-hidden',
            data: {
                notification: true,
            },
            text: '{num, number} {num, plural, =1{notification} other{notifications}}'|t('app', {
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
        {% else %}
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
", '_elements/sources', '/Users/brianhanson/Development/craft5/src/templates/_elements/sources.twig');
    }
}
