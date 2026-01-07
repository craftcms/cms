<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/editableTable.twig */
class __TwigTemplate_0491df49328d8aec1f0e87bf0e6e495f extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'tablecell' => $this->block_tablecell(...),
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_includes/forms/editableTable.twig');
        // line 1
        $context['static'] ??= false;
        // line 2
        $context['fullWidth'] ??= true;
        // line 3
        $context['cols'] ??= [];
        // line 4
        $context['rows'] ??= [];
        // line 5
        $context['initJs'] = (! (isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
            throw new RuntimeError('Variable "static" does not exist.', 5, $this->source);
        })()) && (($context['initJs']) ?? (true)));
        // line 6
        $context['minRows'] ??= null;
        // line 7
        $context['maxRows'] ??= null;
        // line 8
        $context['describedBy'] ??= null;
        // line 10
        $context['totalRows'] = $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['rows']) || array_key_exists('rows', $context) ? $context['rows'] : (function () {
            throw new RuntimeError('Variable "rows" does not exist.', 10, $this->source);
        })()));
        // line 11
        $context['staticRows'] = (((isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
            throw new RuntimeError('Variable "static" does not exist.', 11, $this->source);
        })()) || (($context['staticRows']) ?? (false))) || ((((isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
            throw new RuntimeError('Variable "minRows" does not exist.', 11, $this->source);
        })()) == 1) && ((isset($context['maxRows']) || array_key_exists('maxRows', $context) ? $context['maxRows'] : (function () {
            throw new RuntimeError('Variable "maxRows" does not exist.', 11, $this->source);
        })()) == 1)) && ((isset($context['totalRows']) || array_key_exists('totalRows', $context) ? $context['totalRows'] : (function () {
            throw new RuntimeError('Variable "totalRows" does not exist.', 11, $this->source);
        })()) == 1)));
        // line 12
        $context['allowAdd'] = ((($context['allowAdd']) ?? (false)) && ! (isset($context['staticRows']) || array_key_exists('staticRows', $context) ? $context['staticRows'] : (function () {
            throw new RuntimeError('Variable "staticRows" does not exist.', 12, $this->source);
        })()));
        // line 13
        $context['allowReorder'] = ((($context['allowReorder']) ?? (false)) && ! (isset($context['staticRows']) || array_key_exists('staticRows', $context) ? $context['staticRows'] : (function () {
            throw new RuntimeError('Variable "staticRows" does not exist.', 13, $this->source);
        })()));
        // line 14
        $context['allowDelete'] = ((($context['allowDelete']) ?? (false)) && ! (isset($context['staticRows']) || array_key_exists('staticRows', $context) ? $context['staticRows'] : (function () {
            throw new RuntimeError('Variable "staticRows" does not exist.', 14, $this->source);
        })()));
        // line 15
        echo '
';
        // line 16
        if (! (isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
            throw new RuntimeError('Variable "static" does not exist.', 16, $this->source);
        })())) {
            // line 17
            echo '    ';
            echo craft\helpers\Html::hiddenInput((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 17, $this->source);
            })()), '');
            echo '
';
        }
        // line 19
        echo '
';
        // line 39
        echo '
';
        // line 40
        $context['tableAttributes'] = ['id' =>         // line 41
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 41, $this->source);
})()), 'class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'editable', 1 => ((        // line 44
    (isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
        throw new RuntimeError('Variable "fullWidth" does not exist.', 44, $this->source);
    })())) ? ('fullwidth') : ('')), 2 => ((        // line 45
        (isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
            throw new RuntimeError('Variable "static" does not exist.', 45, $this->source);
        })())) ? ('static') : ('')), 3 => (((        // line 46
            (isset($context['totalRows']) || array_key_exists('totalRows', $context) ? $context['totalRows'] : (function () {
                throw new RuntimeError('Variable "totalRows" does not exist.', 46, $this->source);
            })()) == 0)) ? ('hidden') : (''))]), ];
        // line 50
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 51
            $context['tableAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableAttributes']) || array_key_exists('tableAttributes', $context) ? $context['tableAttributes'] : (function () {
                throw new RuntimeError('Variable "tableAttributes" does not exist.', 51, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 53
        echo '
';
        // line 54
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
            throw new RuntimeError('Variable "cols" does not exist.', 54, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['col']) {
            // line 55
            switch (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'type', [])) {
                case 'time':
                    // line 57
                    craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                        throw new RuntimeError('Variable "view" does not exist.', 57, $this->source);
                    })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\timepicker\\TimepickerAsset'], 'method');
                    break;
                case 'template':
                    // line 59
                    craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                        throw new RuntimeError('Variable "view" does not exist.', 59, $this->source);
                    })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\vue\\VueAsset'], 'method');
                    break;
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['col'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 62
        echo '
<span role="status" class="visually-hidden" data-status-message></span>
';
        // line 64
        ob_start();
        // line 65
        echo '    ';
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
            throw new RuntimeError('Variable "cols" does not exist.', 65, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['col']) {
            // line 66
            echo '        <col>
    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['col'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 68
        echo '    ';
        if (((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
            throw new RuntimeError('Variable "allowDelete" does not exist.', 68, $this->source);
        })()) && (isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
            throw new RuntimeError('Variable "allowReorder" does not exist.', 68, $this->source);
        })()))) {
            // line 69
            echo '        <colgroup span="2"></colgroup>
    ';
        } else {
            // line 71
            echo '        ';
            if ((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                throw new RuntimeError('Variable "allowDelete" does not exist.', 71, $this->source);
            })())) {
                echo '<col>';
            }
            // line 72
            echo '        ';
            if ((isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                throw new RuntimeError('Variable "allowReorder" does not exist.', 72, $this->source);
            })())) {
                echo '<col>';
            }
            // line 73
            echo '    ';
        }
        // line 74
        echo '    ';
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
            throw new RuntimeError('Variable "cols" does not exist.', 74, $this->source);
        })()), function ($__c__) use ($context) {
            $context['c'] = $__c__;

            return ! ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'headingHtml', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'headingHtml', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'headingHtml', [])) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'heading', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'heading', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'heading', [])) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'info', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'info', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'info', [])) : ('')))))) === '');
        }))) {
            // line 75
            echo '        <thead>
            <tr>
                ';
            // line 77
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
                throw new RuntimeError('Variable "cols" does not exist.', 77, $this->source);
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
            foreach ($context['_seq'] as $context['_key'] => $context['col']) {
                // line 78
                echo '                    ';
                $context['columnHeadingId'] = (((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                    throw new RuntimeError('Variable "id" does not exist.', 78, $this->source);
                })()).'-heading-').craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'index', []));
                // line 79
                echo '                    <th id="';
                echo twig_escape_filter($this->env, (isset($context['columnHeadingId']) || array_key_exists('columnHeadingId', $context) ? $context['columnHeadingId'] : (function () {
                    throw new RuntimeError('Variable "columnHeadingId" does not exist.', 79, $this->source);
                })()), 'html', null, true);
                echo '" scope="col" class="';
                echo twig_call_macro($macros['_self'], 'macro_cellClass', [(isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                    throw new RuntimeError('Variable "fullWidth" does not exist.', 79, $this->source);
                })()), $context['col'], (((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [])) : ([]))], 79, $context, $this->getSourceContext());
                echo '">';
                // line 80
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'headingHtml', [], 'any', true, true)) {
                    // line 81
                    echo craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'headingHtml', []);
                } elseif ((((craft\helpers\Template::attribute($this->env, $this->source,                 // line 82
                    $context['col'], 'heading', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'heading', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'heading', [])) : (false))) {
                    // line 83
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'heading', []), 'html', null, true);
                } else {
                    // line 85
                    echo '                            &nbsp;';
                }
                // line 87
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'info', [], 'any', true, true)) {
                    // line 88
                    echo '<span class="info">';
                    echo $this->extensions['craft\web\twig\Extension']->markdownFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'info', []));
                    echo '</span>';
                }
                // line 90
                echo '</th>
                ';
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
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['col'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 92
            echo '                ';
            if (((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                throw new RuntimeError('Variable "allowDelete" does not exist.', 92, $this->source);
            })()) || (isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                throw new RuntimeError('Variable "allowReorder" does not exist.', 92, $this->source);
            })()))) {
                // line 93
                echo '                    <th colspan="';
                echo ((! (isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                    throw new RuntimeError('Variable "allowDelete" does not exist.', 93, $this->source);
                })()) || ! (isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                    throw new RuntimeError('Variable "allowReorder" does not exist.', 93, $this->source);
                })()))) ? (1) : (2);
                echo '" scope="colgroup"><span class="visually-hidden">';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Row actions', 'app'), 'html', null, true);
                echo '</span></th>
                ';
            }
            // line 95
            echo '            </tr>
        </thead>
    ';
        }
        // line 98
        echo '    <tbody>
        ';
        // line 99
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['rows']) || array_key_exists('rows', $context) ? $context['rows'] : (function () {
            throw new RuntimeError('Variable "rows" does not exist.', 99, $this->source);
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
        foreach ($context['_seq'] as $context['rowId'] => $context['row']) {
            // line 100
            echo '            ';
            $context['rowNumber'] = craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'index', []);
            // line 101
            echo '            <tr data-id="';
            echo twig_escape_filter($this->env, $context['rowId'], 'html', null, true);
            echo '">
                ';
            // line 102
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
                throw new RuntimeError('Variable "cols" does not exist.', 102, $this->source);
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
            foreach ($context['_seq'] as $context['colId'] => $context['col']) {
                // line 103
                echo '                    ';
                $context['cell'] = ((craft\helpers\Template::attribute($this->env, $this->source, $context['row'], $context['colId'], [], 'array', true, true)) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['row'], $context['colId'], [], 'array')) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['defaultValues'] ?? null), $context['colId'], [], 'array', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['defaultValues'] ?? null), $context['colId'], [], 'array') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['defaultValues'] ?? null), $context['colId'], [], 'array')) : (null))));
                // line 104
                echo '                    ';
                $context['value'] = ((craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'value', [], 'any', true, true)) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['cell']) || array_key_exists('cell', $context) ? $context['cell'] : (function () {
                    throw new RuntimeError('Variable "cell" does not exist.', 104, $this->source);
                })()), 'value', [])) : ((isset($context['cell']) || array_key_exists('cell', $context) ? $context['cell'] : (function () {
                    throw new RuntimeError('Variable "cell" does not exist.', 104, $this->source);
                })())));
                // line 105
                echo '                    ';
                if ((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'type', []) == 'heading')) {
                    // line 106
                    echo '                        <th scope="row" class="';
                    echo twig_call_macro($macros['_self'], 'macro_cellClass', [(isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                        throw new RuntimeError('Variable "fullWidth" does not exist.', 106, $this->source);
                    })()), $context['col'], (((craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [])) : ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [])) : ([]))))], 106, $context, $this->getSourceContext());
                    echo '"';
                    if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [])) : (false))) {
                        echo ' width="';
                        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', []), 'html', null, true);
                        echo '"';
                    }
                    echo '>';
                    echo isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                        throw new RuntimeError('Variable "value" does not exist.', 106, $this->source);
                    })();
                    echo '</th>
                    ';
                } elseif ((craft\helpers\Template::attribute($this->env, $this->source,                 // line 107
                    $context['col'], 'type', []) == 'html')) {
                    // line 108
                    echo '                        <td class="';
                    echo twig_call_macro($macros['_self'], 'macro_cellClass', [(isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                        throw new RuntimeError('Variable "fullWidth" does not exist.', 108, $this->source);
                    })()), $context['col'], (((craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [])) : ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [])) : ([]))))], 108, $context, $this->getSourceContext());
                    echo '"';
                    if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [])) : (false))) {
                        echo ' width="';
                        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', []), 'html', null, true);
                        echo '"';
                    }
                    echo '>';
                    echo isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                        throw new RuntimeError('Variable "value" does not exist.', 108, $this->source);
                    })();
                    echo '</td>
                    ';
                } else {
                    // line 110
                    echo '                        ';
                    $context['headingId'] = (((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                        throw new RuntimeError('Variable "id" does not exist.', 110, $this->source);
                    })()).'-heading-').craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'index', []));
                    // line 111
                    echo '                        ';
                    $context['hasErrors'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'hasErrors', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'hasErrors', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'hasErrors', [])) : (false));
                    // line 112
                    echo '                        ';
                    $context['cellName'] = ((((((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                        throw new RuntimeError('Variable "name" does not exist.', 112, $this->source);
                    })()).'[').$context['rowId']).'][').$context['colId']).']');
                    // line 113
                    echo '                        ';
                    $context['isCode'] = ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'code', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'code', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'code', [])) : (false)) || (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'type', []) == 'color'));
                    // line 114
                    echo '                        <td class="';
                    echo twig_call_macro($macros['_self'], 'macro_cellClass', [(isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                        throw new RuntimeError('Variable "fullWidth" does not exist.', 114, $this->source);
                    })()), $context['col'], (((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [])) : ([]))], 114, $context, $this->getSourceContext());
                    echo ' ';
                    if ((isset($context['isCode']) || array_key_exists('isCode', $context) ? $context['isCode'] : (function () {
                        throw new RuntimeError('Variable "isCode" does not exist.', 114, $this->source);
                    })())) {
                        echo 'code';
                    }
                    echo ' ';
                    if ((isset($context['hasErrors']) || array_key_exists('hasErrors', $context) ? $context['hasErrors'] : (function () {
                        throw new RuntimeError('Variable "hasErrors" does not exist.', 114, $this->source);
                    })())) {
                        echo 'error';
                    }
                    echo '"';
                    if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [])) : (false))) {
                        echo ' width="';
                        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', []), 'html', null, true);
                        echo '"';
                    }
                    echo '>
                            ';
                    // line 115
                    $this->displayBlock('tablecell', $context, $blocks);
                    // line 214
                    echo '                        </td>
                    ';
                }
                // line 216
                echo '                ';
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
            unset($context['_seq'], $context['_iterated'], $context['colId'], $context['col'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 217
            echo '                ';
            if ((isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                throw new RuntimeError('Variable "allowReorder" does not exist.', 217, $this->source);
            })())) {
                // line 218
                echo '<td class="thin action"><a class="move icon" title="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Reorder', 'app'), 'html', null, true);
                echo '" aria-label="';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Reorder', 'app'), 'html', null, true);
                echo '" type="button" role="button"></a></td>';
            }
            // line 220
            if ((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                throw new RuntimeError('Variable "allowDelete" does not exist.', 220, $this->source);
            })())) {
                // line 221
                echo '<td class="thin action">
                        ';
                // line 222
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'delete', 1 => 'icon', 2 => (((                // line 226
                    (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                        throw new RuntimeError('Variable "minRows" does not exist.', 226, $this->source);
                    })()) && ((isset($context['totalRows']) || array_key_exists('totalRows', $context) ? $context['totalRows'] : (function () {
                        throw new RuntimeError('Variable "totalRows" does not exist.', 226, $this->source);
                    })()) <= (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                        throw new RuntimeError('Variable "minRows" does not exist.', 226, $this->source);
                    })())))) ? ('disabled') : (null))]), 'type' => 'button', 'disabled' => (                // line 229
                        (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                            throw new RuntimeError('Variable "minRows" does not exist.', 229, $this->source);
                        })()) && ((isset($context['totalRows']) || array_key_exists('totalRows', $context) ? $context['totalRows'] : (function () {
                            throw new RuntimeError('Variable "totalRows" does not exist.', 229, $this->source);
                        })()) <= (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                            throw new RuntimeError('Variable "minRows" does not exist.', 229, $this->source);
                        })()))), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'aria' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Delete row {index}', 'app', ['index' =>                 // line 233
                        (isset($context['rowNumber']) || array_key_exists('rowNumber', $context) ? $context['rowNumber'] : (function () {
                            throw new RuntimeError('Variable "rowNumber" does not exist.', 233, $this->source);
                        })()), ])]]);
                // line 236
                echo '
                    </td>';
            }
            // line 239
            echo '</tr>
        ';
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
        unset($context['_seq'], $context['_iterated'], $context['rowId'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 241
        echo '    </tbody>
';
        echo craft\helpers\Html::tag('table', ob_get_clean(),         // line 64
            (isset($context['tableAttributes']) || array_key_exists('tableAttributes', $context) ? $context['tableAttributes'] : (function () {
                throw new RuntimeError('Variable "tableAttributes" does not exist.', 64, $this->source);
            })()));
        // line 243
        echo '
';
        // line 244
        if ((isset($context['allowAdd']) || array_key_exists('allowAdd', $context) ? $context['allowAdd'] : (function () {
            throw new RuntimeError('Variable "allowAdd" does not exist.', 244, $this->source);
        })())) {
            // line 245
            echo '    ';
            $context['buttonText'] = (($context['addRowLabel']) ?? ($this->extensions['craft\web\twig\Extension']->translateFilter('Add a row', 'app')));
            // line 246
            echo '    <button type="button" class="btn dashed add icon" aria-label="';
            echo twig_escape_filter($this->env, (isset($context['buttonText']) || array_key_exists('buttonText', $context) ? $context['buttonText'] : (function () {
                throw new RuntimeError('Variable "buttonText" does not exist.', 246, $this->source);
            })()), 'html', null, true);
            echo '">';
            echo twig_escape_filter($this->env, (isset($context['buttonText']) || array_key_exists('buttonText', $context) ? $context['buttonText'] : (function () {
                throw new RuntimeError('Variable "buttonText" does not exist.', 246, $this->source);
            })()), 'html', null, true);
            echo '</button>
';
        }
        // line 248
        echo '
';
        // line 249
        if ((isset($context['initJs']) || array_key_exists('initJs', $context) ? $context['initJs'] : (function () {
            throw new RuntimeError('Variable "initJs" does not exist.', 249, $this->source);
        })())) {
            // line 250
            echo '    ';
            $context['jsId'] = twig_escape_filter($this->env, $this->env->getFilter('namespaceInputId')->getCallable()((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 250, $this->source);
            })())), 'js');
            // line 251
            echo '    ';
            $context['jsName'] = twig_escape_filter($this->env, $this->env->getFilter('namespaceInputName')->getCallable()((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 251, $this->source);
            })())), 'js');
            // line 252
            echo '    ';
            $context['jsCols'] = $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
                throw new RuntimeError('Variable "cols" does not exist.', 252, $this->source);
            })()));
            // line 253
            echo '    ';
            $context['defaultValues'] ??= null;
            // line 254
            echo '    ';
            ob_start();
            // line 255
            echo '        new Craft.EditableTable("';
            echo twig_escape_filter($this->env, (isset($context['jsId']) || array_key_exists('jsId', $context) ? $context['jsId'] : (function () {
                throw new RuntimeError('Variable "jsId" does not exist.', 255, $this->source);
            })()), 'html', null, true);
            echo '", "';
            echo twig_escape_filter($this->env, (isset($context['jsName']) || array_key_exists('jsName', $context) ? $context['jsName'] : (function () {
                throw new RuntimeError('Variable "jsName" does not exist.', 255, $this->source);
            })()), 'html', null, true);
            echo '", ';
            echo isset($context['jsCols']) || array_key_exists('jsCols', $context) ? $context['jsCols'] : (function () {
                throw new RuntimeError('Variable "jsCols" does not exist.', 255, $this->source);
            })();
            echo ', {
            defaultValues: ';
            // line 256
            echo ((isset($context['defaultValues']) || array_key_exists('defaultValues', $context) ? $context['defaultValues'] : (function () {
                throw new RuntimeError('Variable "defaultValues" does not exist.', 256, $this->source);
            })())) ? ($this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['defaultValues']) || array_key_exists('defaultValues', $context) ? $context['defaultValues'] : (function () {
                throw new RuntimeError('Variable "defaultValues" does not exist.', 256, $this->source);
            })()))) : ('{}');
            echo ',
            allowAdd: ';
            // line 257
            echo ((isset($context['allowAdd']) || array_key_exists('allowAdd', $context) ? $context['allowAdd'] : (function () {
                throw new RuntimeError('Variable "allowAdd" does not exist.', 257, $this->source);
            })())) ? ('true') : ('false');
            echo ',
            allowDelete: ';
            // line 258
            echo ((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                throw new RuntimeError('Variable "allowDelete" does not exist.', 258, $this->source);
            })())) ? ('true') : ('false');
            echo ',
            allowReorder: ';
            // line 259
            echo ((isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                throw new RuntimeError('Variable "allowReorder" does not exist.', 259, $this->source);
            })())) ? ('true') : ('false');
            echo ',
            minRows: ';
            // line 260
            (((isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                throw new RuntimeError('Variable "minRows" does not exist.', 260, $this->source);
            })())) ? (print twig_escape_filter($this->env, (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                throw new RuntimeError('Variable "minRows" does not exist.', 260, $this->source);
            })()), 'html', null, true)) : (print 'null'));
            echo ',
            maxRows: ';
            // line 261
            (((isset($context['maxRows']) || array_key_exists('maxRows', $context) ? $context['maxRows'] : (function () {
                throw new RuntimeError('Variable "maxRows" does not exist.', 261, $this->source);
            })())) ? (print twig_escape_filter($this->env, (isset($context['maxRows']) || array_key_exists('maxRows', $context) ? $context['maxRows'] : (function () {
                throw new RuntimeError('Variable "maxRows" does not exist.', 261, $this->source);
            })()), 'html', null, true)) : (print 'null'));
            echo '
        });
    ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        craft\helpers\Template::endProfile('template', '_includes/forms/editableTable.twig');
    }

    // line 115
    public function block_tablecell($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'tablecell');
        // line 116
        switch (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
            throw new RuntimeError('Variable "col" does not exist.', 116, $this->source);
        })()), 'type', [])) {
            case 'checkbox' :
                // line 118
                echo '<div class="checkbox-wrapper">
                                            ';
                // line 119
                $this->loadTemplate('_includes/forms/checkbox', '_includes/forms/editableTable.twig', 119)->display(twig_to_array(['name' =>                 // line 120
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 120, $this->source);
})()), 'value' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 121
    ($context['col'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'value', [])) : (1)), 'checked' => ! twig_test_empty(                // line 122
        (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
            throw new RuntimeError('Variable "value" does not exist.', 122, $this->source);
        })())), 'disabled' =>                 // line 123
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 123, $this->source);
})()), 'labelledBy' =>                 // line 124
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 124, $this->source);
})()), 'describedBy' =>                 // line 125
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 125, $this->source);
})()), ]));
                // line 127
                echo '                                        </div>';
                break;
            case 'color' :
                // line 129
                $this->loadTemplate('_includes/forms/color', '_includes/forms/editableTable.twig', 129)->display(twig_to_array(['name' =>                 // line 130
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 130, $this->source);
})()), 'value' =>                 // line 131
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 131, $this->source);
})()), 'small' => true, 'disabled' =>                 // line 133
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 133, $this->source);
})()), 'labelledBy' =>                 // line 134
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 134, $this->source);
})()), 'describedBy' =>                 // line 135
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 135, $this->source);
})()), ]));
                break;
            case 'date' :
                // line 138
                $this->loadTemplate('_includes/forms/date', '_includes/forms/editableTable.twig', 138)->display(twig_to_array(['name' =>                 // line 139
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 139, $this->source);
})()), 'value' =>                 // line 140
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 140, $this->source);
})()), 'disabled' =>                 // line 141
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 141, $this->source);
})()), 'labelledBy' =>                 // line 142
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 142, $this->source);
})()), 'describedBy' =>                 // line 143
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 143, $this->source);
})()), ]));
                break;
            case 'lightswitch' :
                // line 146
                $this->loadTemplate('_includes/forms/lightswitch', '_includes/forms/editableTable.twig', 146)->display(twig_to_array(['name' =>                 // line 147
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 147, $this->source);
})()), 'on' =>                 // line 148
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 148, $this->source);
})()), 'value' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 149
    ($context['col'] ?? null), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'value', [])) : (1)), 'small' => true, 'disabled' =>                 // line 151
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 151, $this->source);
})()), 'labelledBy' =>                 // line 152
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 152, $this->source);
})()), 'describedBy' =>                 // line 153
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 153, $this->source);
})()), ]));
                // line 155
                echo '                                    ';
                break;
            case 'select' :
                // line 156
                $this->loadTemplate('_includes/forms/select', '_includes/forms/editableTable.twig', 156)->display(twig_to_array(['class' => 'small', 'name' =>                 // line 158
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 158, $this->source);
})()), 'options' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 159
    ($context['cell'] ?? null), 'options', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'options', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'options', [])) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
        throw new RuntimeError('Variable "col" does not exist.', 159, $this->source);
    })()), 'options', []))), 'value' =>                 // line 160
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 160, $this->source);
})()), 'disabled' =>                 // line 161
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 161, $this->source);
})()), 'labelledBy' =>                 // line 162
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 162, $this->source);
})()), 'describedBy' =>                 // line 163
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 163, $this->source);
})()), ]));
                break;
            case 'time' :
                // line 166
                $this->loadTemplate('_includes/forms/time', '_includes/forms/editableTable.twig', 166)->display(twig_to_array(['name' =>                 // line 167
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 167, $this->source);
})()), 'value' =>                 // line 168
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 168, $this->source);
})()), 'disabled' =>                 // line 169
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 169, $this->source);
})()), 'labelledBy' =>                 // line 170
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 170, $this->source);
})()), 'describedBy' =>                 // line 171
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 171, $this->source);
})()), ]));
                break;
            case 'email' :
            case 'url' :
                // line 174
                $this->loadTemplate('_includes/forms/text', '_includes/forms/editableTable.twig', 174)->display(twig_to_array(['type' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 175
                    (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
                        throw new RuntimeError('Variable "col" does not exist.', 175, $this->source);
                    })()), 'type', []), 'name' =>                 // line 176
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 176, $this->source);
})()), 'placeholder' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 177
    ($context['col'] ?? null), 'placeholder', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'placeholder', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'placeholder', [])) : (null)), 'value' =>                 // line 178
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 178, $this->source);
})()), 'disabled' =>                 // line 179
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 179, $this->source);
})()), 'labelledBy' =>                 // line 180
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 180, $this->source);
})()), 'describedBy' =>                 // line 181
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 181, $this->source);
})()), ]));
                break;
            case 'autosuggest' :
            case 'template' :
                // line 184
                $this->loadTemplate('_includes/forms/autosuggest', '_includes/forms/editableTable.twig', 184)->display(twig_to_array(['name' =>                 // line 185
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 185, $this->source);
})()), 'suggestions' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 186
    (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
        throw new RuntimeError('Variable "col" does not exist.', 186, $this->source);
    })()), 'type', []) == 'template')) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
        throw new RuntimeError('Variable "craft" does not exist.', 186, $this->source);
    })()), 'cp', []), 'getTemplateSuggestions', [], 'method')) : ([])), 'suggestEnvVars' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 187
        ($context['col'] ?? null), 'suggestEnvVars', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'suggestEnvVars', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'suggestEnvVars', [])) : (false)), 'suggestAliases' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 188
            ($context['col'] ?? null), 'suggestAliases', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'suggestAliases', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'suggestAliases', [])) : (false)), 'value' =>                 // line 189
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 189, $this->source);
})()), 'disabled' =>                 // line 190
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 190, $this->source);
})()), 'labelledBy' =>                 // line 191
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 191, $this->source);
})()), 'describedBy' =>                 // line 192
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 192, $this->source);
})()), ]));
                break;
            default :
                // line 195
                if ((isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
                    throw new RuntimeError('Variable "static" does not exist.', 195, $this->source);
                })())) {
                    // line 196
                    echo '                                            <pre class="noteditable">';
                    echo twig_escape_filter($this->env, (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                        throw new RuntimeError('Variable "value" does not exist.', 196, $this->source);
                    })()), 'html', null, true);
                    echo '</pre>
                                        ';
                } else {
                    // line 198
                    echo '                                            ';
                    if ((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                        throw new RuntimeError('Variable "value" does not exist.', 198, $this->source);
                    })())) {
                        // line 199
                        echo '                                                <div class="editable-table-preview" aria-hidden="true">';
                        echo twig_escape_filter($this->env, (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                            throw new RuntimeError('Variable "value" does not exist.', 199, $this->source);
                        })()), 'html', null, true);
                        echo '</div>
                                            ';
                    }
                    // line 201
                    echo '                                            ';
                    echo $this->extensions['craft\web\twig\Extension']->tagFunction('textarea', ['name' =>                     // line 202
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 202, $this->source);
})()), 'rows' => (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 203
    ($context['col'] ?? null), 'rows', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'rows', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'rows', [])) : (1)), 'placeholder' => (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 204
        ($context['col'] ?? null), 'placeholder', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'placeholder', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'placeholder', [])) : (false)), 'aria' => ['labelledby' =>                     // line 206
        (isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
            throw new RuntimeError('Variable "headingId" does not exist.', 206, $this->source);
        })()), 'describedby' =>                     // line 207
        (isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
            throw new RuntimeError('Variable "describedBy" does not exist.', 207, $this->source);
        })()), ], 'html' =>                     // line 209
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 209, $this->source);
})()), ]);
                    // line 210
                    echo '
                                        ';
                }
        }
        craft\helpers\Template::endProfile('block', 'tablecell');
    }

    // line 20
    public function macro_cellClass($__fullWidth__ = null, $__col__ = null, $__class__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'fullWidth' => $__fullWidth__,
            'col' => $__col__,
            'class' => $__class__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'cellClass');
            // line 21
            echo twig_escape_filter($this->env, twig_join_filter($this->extensions['craft\web\twig\Extension']->mergeFilter(((twig_test_iterable((isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
                throw new RuntimeError('Variable "class" does not exist.', 21, $this->source);
            })()))) ? ((isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
                throw new RuntimeError('Variable "class" does not exist.', 21, $this->source);
            })())) : ([0 => (isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
                throw new RuntimeError('Variable "class" does not exist.', 21, $this->source);
            })())])), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => (craft\helpers\Template::attribute($this->env, $this->source,             // line 22
                (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
                    throw new RuntimeError('Variable "col" does not exist.', 22, $this->source);
                })()), 'type', []).'-cell'), 1 => ((twig_in_filter(craft\helpers\Template::attribute($this->env, $this->source,             // line 23
                    (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
                        throw new RuntimeError('Variable "col" does not exist.', 23, $this->source);
                    })()), 'type', []), [0 => 'autosuggest', 1 => 'color', 2 => 'date', 3 => 'email', 4 => 'multiline', 5 => 'number', 6 => 'singleline', 7 => 'template', 8 => 'time', 9 => 'url'])) ? ('textual') : (null)), 2 => (((            // line 35
                        (isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                            throw new RuntimeError('Variable "fullWidth" does not exist.', 35, $this->source);
                        })()) && (((craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'thin', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'thin', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'thin', [])) : (false)))) ? ('thin') : (null)), 3 => ((craft\helpers\Template::attribute($this->env, $this->source,             // line 36
                            ($context['col'] ?? null), 'info', [], 'any', true, true)) ? ('has-info') : (null))])), ' '), 'html', null, true);
            craft\helpers\Template::endProfile('macro', 'cellClass');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_includes/forms/editableTable.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [663 => 36,  662 => 35,  661 => 23,  660 => 22,  659 => 21,  643 => 20,  634 => 210,  632 => 209,  631 => 207,  630 => 206,  629 => 204,  628 => 203,  627 => 202,  625 => 201,  619 => 199,  616 => 198,  610 => 196,  608 => 195,  602 => 192,  601 => 191,  600 => 190,  599 => 189,  598 => 188,  597 => 187,  596 => 186,  595 => 185,  594 => 184,  587 => 181,  586 => 180,  585 => 179,  584 => 178,  583 => 177,  582 => 176,  581 => 175,  580 => 174,  573 => 171,  572 => 170,  571 => 169,  570 => 168,  569 => 167,  568 => 166,  562 => 163,  561 => 162,  560 => 161,  559 => 160,  558 => 159,  557 => 158,  556 => 156,  550 => 155,  548 => 153,  547 => 152,  546 => 151,  545 => 149,  544 => 148,  543 => 147,  542 => 146,  536 => 143,  535 => 142,  534 => 141,  533 => 140,  532 => 139,  531 => 138,  525 => 135,  524 => 134,  523 => 133,  522 => 131,  521 => 130,  520 => 129,  514 => 127,  512 => 125,  511 => 124,  510 => 123,  509 => 122,  508 => 121,  507 => 120,  506 => 119,  503 => 118,  499 => 116,  494 => 115,  484 => 261,  480 => 260,  476 => 259,  472 => 258,  468 => 257,  464 => 256,  455 => 255,  452 => 254,  449 => 253,  446 => 252,  443 => 251,  440 => 250,  438 => 249,  435 => 248,  427 => 246,  424 => 245,  422 => 244,  419 => 243,  417 => 64,  414 => 241,  399 => 239,  395 => 236,  393 => 233,  392 => 229,  391 => 226,  390 => 222,  387 => 221,  385 => 220,  378 => 218,  375 => 217,  361 => 216,  357 => 214,  355 => 115,  336 => 114,  333 => 113,  330 => 112,  327 => 111,  324 => 110,  310 => 108,  308 => 107,  295 => 106,  292 => 105,  289 => 104,  286 => 103,  269 => 102,  264 => 101,  261 => 100,  244 => 99,  241 => 98,  236 => 95,  228 => 93,  225 => 92,  210 => 90,  205 => 88,  203 => 87,  200 => 85,  197 => 83,  195 => 82,  193 => 81,  191 => 80,  185 => 79,  182 => 78,  165 => 77,  161 => 75,  158 => 74,  155 => 73,  150 => 72,  145 => 71,  141 => 69,  138 => 68,  131 => 66,  126 => 65,  124 => 64,  120 => 62,  111 => 59,  105 => 57,  101 => 55,  97 => 54,  94 => 53,  91 => 51,  89 => 50,  87 => 46,  86 => 45,  85 => 44,  84 => 41,  83 => 40,  80 => 39,  77 => 19,  71 => 17,  69 => 16,  66 => 15,  64 => 14,  62 => 13,  60 => 12,  58 => 11,  56 => 10,  54 => 8,  52 => 7,  50 => 6,  48 => 5,  46 => 4,  44 => 3,  42 => 2,  40 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{%- set static = static ?? false %}
{%- set fullWidth = fullWidth ?? true %}
{%- set cols = cols ?? [] %}
{%- set rows = rows ?? [] %}
{%- set initJs = not static and (initJs ?? true) -%}
{%- set minRows = minRows ?? null %}
{%- set maxRows = maxRows ?? null %}
{%- set describedBy = describedBy ?? null %}

{%- set totalRows = rows|length %}
{%- set staticRows = static or (staticRows ?? false) or (minRows == 1 and maxRows == 1 and totalRows == 1) %}
{%- set allowAdd = (allowAdd ?? false) and not staticRows %}
{%- set allowReorder = (allowReorder ?? false) and not staticRows %}
{%- set allowDelete = (allowDelete ?? false) and not staticRows %}

{% if not static %}
    {{ hiddenInput(name, '') }}
{% endif %}

{% macro cellClass(fullWidth, col, class) %}
    {{- (class is iterable ? class : [class])|merge([
        \"#{col.type}-cell\",
        col.type in [
            'autosuggest',
            'color',
            'date',
            'email',
            'multiline',
            'number',
            'singleline',
            'template',
            'time',
            'url',
        ] ? 'textual' : null,
        fullWidth and (col.thin ?? false) ? 'thin' : null,
        col.info is defined ? 'has-info' : null,
    ]|filter)|join(' ') -}}
{% endmacro %}

{% set tableAttributes = {
    id: id,
    class: [
        'editable',
        fullWidth ? 'fullwidth',
        static ? 'static',
        totalRows == 0 ? 'hidden',
    ]|filter,
} %}

{%- if block('attr') is defined %}
  {%- set tableAttributes = tableAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{% endif %}

{% for col in cols %}
    {%- switch col.type %}
        {%- case 'time' %}
            {%- do view.registerAssetBundle('craft\\\\web\\\\assets\\\\timepicker\\\\TimepickerAsset') %}
        {%- case 'template' %}
            {%- do view.registerAssetBundle(\"craft\\\\web\\\\assets\\\\vue\\\\VueAsset\") %}
    {%- endswitch %}
{% endfor %}

<span role=\"status\" class=\"visually-hidden\" data-status-message></span>
{% tag 'table' with tableAttributes %}
    {% for col in cols %}
        <col>
    {% endfor %}
    {% if (allowDelete and allowReorder) %}
        <colgroup span=\"2\"></colgroup>
    {% else %}
        {% if allowDelete %}<col>{% endif %}
        {% if allowReorder %}<col>{% endif %}
    {% endif %}
    {% if cols|filter(c => (c.headingHtml ?? c.heading ?? c.info ?? '') is not same as(''))|length %}
        <thead>
            <tr>
                {% for col in cols %}
                    {% set columnHeadingId = \"#{id}-heading-#{loop.index}\" %}
                    <th id=\"{{ columnHeadingId }}\" scope=\"col\" class=\"{{ _self.cellClass(fullWidth, col, col.class ?? []) }}\">
                        {%- if col.headingHtml is defined %}
                            {{- col.headingHtml|raw }}
                        {%- elseif col.heading ?? false %}
                            {{- col.heading }}
                        {%- else %}
                            &nbsp;
                        {%- endif %}
                        {%- if col.info is defined -%}
                            <span class=\"info\">{{ col.info|md|raw }}</span>
                        {%- endif -%}
                    </th>
                {% endfor %}
                {% if (allowDelete or allowReorder) %}
                    <th colspan=\"{{ not allowDelete or not allowReorder ? 1 : 2 }}\" scope=\"colgroup\"><span class=\"visually-hidden\">{{ 'Row actions'|t('app') }}</span></th>
                {% endif %}
            </tr>
        </thead>
    {% endif %}
    <tbody>
        {% for rowId, row in rows %}
            {% set rowNumber = loop.index %}
            <tr data-id=\"{{ rowId }}\">
                {% for colId, col in cols %}
                    {% set cell = row[colId] is defined ? row[colId] : (defaultValues[colId] ?? null) %}
                    {% set value = cell.value is defined ? cell.value : cell %}
                    {% if col.type == 'heading' %}
                        <th scope=\"row\" class=\"{{ _self.cellClass(fullWidth, col, cell.class ?? col.class ?? []) }}\"{% if col.width ?? false %} width=\"{{ col.width }}\"{% endif %}>{{ value|raw }}</th>
                    {% elseif col.type == 'html' %}
                        <td class=\"{{ _self.cellClass(fullWidth, col, cell.class ?? col.class ?? []) }}\"{% if col.width ?? false %} width=\"{{ col.width }}\"{% endif %}>{{ value|raw }}</td>
                    {% else %}
                        {% set headingId = \"#{id}-heading-#{loop.index}\" %}
                        {% set hasErrors = cell.hasErrors ?? false %}
                        {% set cellName = name~'['~rowId~']['~colId~']' %}
                        {% set isCode = (col.code ?? false) or col.type == 'color' %}
                        <td class=\"{{ _self.cellClass(fullWidth, col, col.class ?? []) }} {% if isCode %}code{% endif %} {% if hasErrors %}error{% endif %}\"{% if col.width ?? false %} width=\"{{ col.width }}\"{% endif %}>
                            {% block tablecell %}
                                {%- switch col.type -%}
                                    {%- case 'checkbox' -%}
                                        <div class=\"checkbox-wrapper\">
                                            {% include \"_includes/forms/checkbox\" with {
                                                name: cellName,
                                                value:  col.value ?? 1,
                                                checked: value is not empty,
                                                disabled: static,
                                                labelledBy: headingId,
                                                describedBy: describedBy,
                                            } only %}
                                        </div>
                                    {%- case 'color' -%}
                                        {% include \"_includes/forms/color\" with {
                                            name: cellName,
                                            value: value,
                                            small: true,
                                            disabled: static,
                                            labelledBy: headingId,
                                            describedBy: describedBy,
                                        } only %}
                                    {%- case 'date' -%}
                                        {% include \"_includes/forms/date\" with {
                                            name: cellName,
                                            value: value,
                                            disabled: static,
                                            labelledBy: headingId,
                                            describedBy: describedBy,
                                        } only %}
                                    {%- case 'lightswitch' -%}
                                        {% include \"_includes/forms/lightswitch\" with {
                                            name: cellName,
                                            on: value,
                                            value: col.value ?? 1,
                                            small: true,
                                            disabled: static,
                                            labelledBy: headingId,
                                            describedBy: describedBy,
                                        } only %}
                                    {% case 'select' -%}
                                        {% include \"_includes/forms/select\" with {
                                            class: 'small',
                                            name: cellName,
                                            options: cell.options ?? col.options,
                                            value: value,
                                            disabled: static,
                                            labelledBy: headingId,
                                            describedBy: describedBy,
                                        } only %}
                                    {%- case 'time' -%}
                                        {% include \"_includes/forms/time\" with {
                                            name: cellName,
                                            value: value,
                                            disabled: static,
                                            labelledBy: headingId,
                                            describedBy: describedBy,
                                        } only %}
                                    {%- case 'email' or 'url' -%}
                                        {% include \"_includes/forms/text\" with {
                                            type: col.type,
                                            name: cellName,
                                            placeholder: col.placeholder ?? null,
                                            value:  value,
                                            disabled: static,
                                            labelledBy: headingId,
                                            describedBy: describedBy,
                                        } only %}
                                    {%- case 'autosuggest' or 'template' -%}
                                        {% include \"_includes/forms/autosuggest\" with {
                                            name: cellName,
                                            suggestions: col.type == 'template' ? craft.cp.getTemplateSuggestions() : [],
                                            suggestEnvVars: col.suggestEnvVars ?? false,
                                            suggestAliases: col.suggestAliases ?? false,
                                            value: value,
                                            disabled: static,
                                            labelledBy: headingId,
                                            describedBy: describedBy,
                                        } only %}
                                    {%- default -%}
                                        {% if static %}
                                            <pre class=\"noteditable\">{{ value }}</pre>
                                        {% else %}
                                            {% if value %}
                                                <div class=\"editable-table-preview\" aria-hidden=\"true\">{{ value }}</div>
                                            {% endif %}
                                            {{ tag('textarea', {
                                                name: cellName,
                                                rows: col.rows ?? 1,
                                                placeholder: col.placeholder ?? false,
                                                aria: {
                                                    labelledby: headingId,
                                                    describedby: describedBy,
                                                },
                                                html: value,
                                            }) }}
                                        {% endif %}
                                {%- endswitch -%}
                            {% endblock %}
                        </td>
                    {% endif %}
                {% endfor %}
                {% if allowReorder -%}
                    <td class=\"thin action\"><a class=\"move icon\" title=\"{{ 'Reorder'|t('app') }}\" aria-label=\"{{ 'Reorder'|t('app') }}\" type=\"button\" role=\"button\"></a></td>
                {%- endif -%}
                {%- if allowDelete -%}
                    <td class=\"thin action\">
                        {{ tag('button', {
                            class: [
                                'delete',
                                'icon',
                                minRows and totalRows <= minRows ? 'disabled' : null,
                            ]|filter,
                            type: 'button',
                            disabled: minRows and totalRows <= minRows,
                            title: 'Delete'|t('app'),
                            aria: {
                                label: 'Delete row {index}'|t('app', {
                                    index: rowNumber,
                                }),
                            }
                        }) }}
                    </td>
                {%- endif -%}
            </tr>
        {% endfor %}
    </tbody>
{% endtag %}

{% if allowAdd %}
    {% set buttonText = addRowLabel ?? \"Add a row\"|t('app') %}
    <button type=\"button\" class=\"btn dashed add icon\" aria-label=\"{{ buttonText }}\">{{ buttonText }}</button>
{% endif %}

{% if initJs %}
    {% set jsId = id|namespaceInputId|e('js') %}
    {% set jsName = name|namespaceInputName|e('js') %}
    {% set jsCols = cols|json_encode %}
    {% set defaultValues = defaultValues ?? null %}
    {% js %}
        new Craft.EditableTable(\"{{ jsId }}\", \"{{ jsName }}\", {{ jsCols|raw }}, {
            defaultValues: {{ defaultValues ? defaultValues|json_encode|raw : '{}' }},
            allowAdd: {{ allowAdd ? 'true' : 'false' }},
            allowDelete: {{ allowDelete ? 'true' : 'false' }},
            allowReorder: {{ allowReorder ? 'true' : 'false' }},
            minRows: {{ minRows ? minRows : 'null' }},
            maxRows: {{ maxRows ? maxRows : 'null' }}
        });
    {% endjs %}
{% endif %}
", '_includes/forms/editableTable.twig', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/editableTable.twig');
    }
}
