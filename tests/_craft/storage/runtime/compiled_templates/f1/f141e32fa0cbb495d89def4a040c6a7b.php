<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/editableTable.twig */
class __TwigTemplate_7055626fa28e7c418eca25c51ee15381 extends Template
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
            'tablecell' => $this->block_tablecell(...),
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
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
        yield '
';
        // line 16
        $context['actionMenuItems'] = [['icon' => 'arrow-up', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Move up', 'app'), 'attributes' => ['data' => ['action' => 'moveUp']]], ['icon' => 'arrow-down', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Move down', 'app'), 'attributes' => ['data' => ['action' => 'moveDown']]]];
        // line 32
        yield '
';
        // line 33
        if (! (isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
            throw new RuntimeError('Variable "static" does not exist.', 33, $this->source);
        })())) {
            // line 34
            yield '    ';
            yield craft\helpers\Html::hiddenInput((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 34, $this->source);
            })()), '');
            yield '
';
        }
        // line 36
        yield '
';
        // line 56
        yield '
';
        // line 57
        $context['tableAttributes'] = ['id' =>         // line 58
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 58, $this->source);
})()), 'class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['editable', ((        // line 61
    (isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
        throw new RuntimeError('Variable "fullWidth" does not exist.', 61, $this->source);
    })())) ? ('fullwidth') : ('')), ((        // line 62
        (isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
            throw new RuntimeError('Variable "static" does not exist.', 62, $this->source);
        })())) ? ('static') : ('')), (((        // line 63
            (isset($context['totalRows']) || array_key_exists('totalRows', $context) ? $context['totalRows'] : (function () {
                throw new RuntimeError('Variable "totalRows" does not exist.', 63, $this->source);
            })()) == 0)) ? ('hidden') : (''))])];
        // line 67
        if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
            // line 68
            $context['tableAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['tableAttributes']) || array_key_exists('tableAttributes', $context) ? $context['tableAttributes'] : (function () {
                throw new RuntimeError('Variable "tableAttributes" does not exist.', 68, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 70
        yield '
';
        // line 71
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
            throw new RuntimeError('Variable "cols" does not exist.', 71, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['col']) {
            // line 72
            switch (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'type', [], 'any', false, false, false, 72)) {
                case 'time':
                    // line 74
                    craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                        throw new RuntimeError('Variable "view" does not exist.', 74, $this->source);
                    })()), 'registerAssetBundle', ['craft\\web\\assets\\timepicker\\TimepickerAsset'], 'method', false, false, false, 74);
                    break;
                case 'template':
                    // line 76
                    craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                        throw new RuntimeError('Variable "view" does not exist.', 76, $this->source);
                    })()), 'registerAssetBundle', ['craft\\web\\assets\\vue\\VueAsset'], 'method', false, false, false, 76);
                    break;
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['col'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 79
        yield '
<span role="status" class="visually-hidden" data-status-message></span>
';
        // line 81
        ob_start();
        // line 82
        yield '    ';
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
            throw new RuntimeError('Variable "cols" does not exist.', 82, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['col']) {
            // line 83
            yield '        <col>
    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['col'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 85
        yield '    ';
        if (((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
            throw new RuntimeError('Variable "allowDelete" does not exist.', 85, $this->source);
        })()) && (isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
            throw new RuntimeError('Variable "allowReorder" does not exist.', 85, $this->source);
        })()))) {
            // line 86
            yield '        <colgroup span="2"></colgroup>
    ';
        } else {
            // line 88
            yield '        ';
            if ((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                throw new RuntimeError('Variable "allowDelete" does not exist.', 88, $this->source);
            })())) {
                yield '<col>';
            }
            // line 89
            yield '        ';
            if ((isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                throw new RuntimeError('Variable "allowReorder" does not exist.', 89, $this->source);
            })())) {
                yield '<col>';
            }
            // line 90
            yield '    ';
        }
        // line 91
        yield '    ';
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
            throw new RuntimeError('Variable "cols" does not exist.', 91, $this->source);
        })()), function ($__c__) use ($context) {
            $context['c'] = $__c__;

            return ! ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'headingHtml', [], 'any', true, true, false, 91) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'headingHtml', [], 'any', false, false, false, 91) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'headingHtml', [], 'any', false, false, false, 91)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'heading', [], 'any', true, true, false, 91) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'heading', [], 'any', false, false, false, 91) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'heading', [], 'any', false, false, false, 91)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'info', [], 'any', true, true, false, 91) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'info', [], 'any', false, false, false, 91) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['c'] ?? null), 'info', [], 'any', false, false, false, 91)) : ('')))))) === '');
        }))) {
            // line 92
            yield '        <thead>
            <tr>
                ';
            // line 94
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
                throw new RuntimeError('Variable "cols" does not exist.', 94, $this->source);
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
                // line 95
                yield '                    ';
                $context['columnHeadingId'] = (((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                    throw new RuntimeError('Variable "id" does not exist.', 95, $this->source);
                })()).'-heading-').craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'index', [], 'any', false, false, false, 95));
                // line 96
                yield '                    <th id="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['columnHeadingId']) || array_key_exists('columnHeadingId', $context) ? $context['columnHeadingId'] : (function () {
                    throw new RuntimeError('Variable "columnHeadingId" does not exist.', 96, $this->source);
                })()), 'html', null, true);
                yield '" scope="col" class="';
                yield CoreExtension::callMacro($macros['_self'], 'macro_cellClass', [(isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                    throw new RuntimeError('Variable "fullWidth" does not exist.', 96, $this->source);
                })()), $context['col'], (((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', true, true, false, 96) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', false, false, false, 96) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', false, false, false, 96)) : ([]))], 96, $context, $this->getSourceContext());
                yield '">';
                // line 97
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'headingHtml', [], 'any', true, true, false, 97)) {
                    // line 98
                    yield craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'headingHtml', [], 'any', false, false, false, 98);
                } elseif ((((craft\helpers\Template::attribute($this->env, $this->source,                 // line 99
                    $context['col'], 'heading', [], 'any', true, true, false, 99) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'heading', [], 'any', false, false, false, 99) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'heading', [], 'any', false, false, false, 99)) : (false))) {
                    // line 100
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'heading', [], 'any', false, false, false, 100), 'html', null, true);
                } else {
                    // line 102
                    yield '                            &nbsp;';
                }
                // line 104
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'info', [], 'any', true, true, false, 104)) {
                    // line 105
                    yield '<span class="info">';
                    yield $this->extensions['craft\web\twig\Extension']->markdownFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'info', [], 'any', false, false, false, 105));
                    yield '</span>';
                }
                // line 107
                yield '</th>
                ';
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
            unset($context['_seq'], $context['_key'], $context['col'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 109
            yield '                ';
            if (((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                throw new RuntimeError('Variable "allowDelete" does not exist.', 109, $this->source);
            })()) || (isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                throw new RuntimeError('Variable "allowReorder" does not exist.', 109, $this->source);
            })()))) {
                // line 110
                yield '                    <th colspan="';
                yield ((! (isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                    throw new RuntimeError('Variable "allowDelete" does not exist.', 110, $this->source);
                })()) || ! (isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                    throw new RuntimeError('Variable "allowReorder" does not exist.', 110, $this->source);
                })()))) ? (1) : (2);
                yield '" scope="colgroup"><span class="visually-hidden">';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Row actions', 'app'), 'html', null, true);
                yield '</span></th>
                ';
            }
            // line 112
            yield '            </tr>
        </thead>
    ';
        }
        // line 115
        yield '    <tbody>
        ';
        // line 116
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['rows']) || array_key_exists('rows', $context) ? $context['rows'] : (function () {
            throw new RuntimeError('Variable "rows" does not exist.', 116, $this->source);
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
            // line 117
            yield '            ';
            $context['rowNumber'] = craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'index', [], 'any', false, false, false, 117);
            // line 118
            yield '            ';
            $context['rowName'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Row {index}', 'app', ['index' => (isset($context['rowNumber']) || array_key_exists('rowNumber', $context) ? $context['rowNumber'] : (function () {
                throw new RuntimeError('Variable "rowNumber" does not exist.', 118, $this->source);
            })())]);
            // line 119
            yield '            ';
            $context['actionBtnLabel'] = (((isset($context['rowName']) || array_key_exists('rowName', $context) ? $context['rowName'] : (function () {
                throw new RuntimeError('Variable "rowName" does not exist.', 119, $this->source);
            })()).' ').$this->extensions['craft\web\twig\Extension']->translateFilter('Actions', 'app'));
            // line 120
            yield '            <tr data-id="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['rowId'], 'html', null, true);
            yield '">
                ';
            // line 121
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
                throw new RuntimeError('Variable "cols" does not exist.', 121, $this->source);
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
                // line 122
                yield '                    ';
                $context['cell'] = ((craft\helpers\Template::attribute($this->env, $this->source, $context['row'], $context['colId'], [], 'array', true, true, false, 122)) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['row'], $context['colId'], [], 'array', false, false, false, 122)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['defaultValues'] ?? null), $context['colId'], [], 'array', true, true, false, 122) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['defaultValues'] ?? null), $context['colId'], [], 'array', false, false, false, 122) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['defaultValues'] ?? null), $context['colId'], [], 'array', false, false, false, 122)) : (null))));
                // line 123
                yield '                    ';
                $context['value'] = ((craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'value', [], 'any', true, true, false, 123)) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['cell']) || array_key_exists('cell', $context) ? $context['cell'] : (function () {
                    throw new RuntimeError('Variable "cell" does not exist.', 123, $this->source);
                })()), 'value', [], 'any', false, false, false, 123)) : ((isset($context['cell']) || array_key_exists('cell', $context) ? $context['cell'] : (function () {
                    throw new RuntimeError('Variable "cell" does not exist.', 123, $this->source);
                })())));
                // line 124
                yield '                    ';
                if ((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'type', [], 'any', false, false, false, 124) == 'heading')) {
                    // line 125
                    yield '                        <th scope="row" class="';
                    yield CoreExtension::callMacro($macros['_self'], 'macro_cellClass', [(isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                        throw new RuntimeError('Variable "fullWidth" does not exist.', 125, $this->source);
                    })()), $context['col'], (((craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [], 'any', true, true, false, 125) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [], 'any', false, false, false, 125) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [], 'any', false, false, false, 125)) : ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', true, true, false, 125) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', false, false, false, 125) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', false, false, false, 125)) : ([]))))], 125, $context, $this->getSourceContext());
                    yield '"';
                    if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', true, true, false, 125) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 125) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 125)) : (false))) {
                        yield ' width="';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 125), 'html', null, true);
                        yield '"';
                    }
                    yield '>';
                    yield isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                        throw new RuntimeError('Variable "value" does not exist.', 125, $this->source);
                    })();
                    yield '</th>
                    ';
                } elseif ((craft\helpers\Template::attribute($this->env, $this->source,                 // line 126
                    $context['col'], 'type', [], 'any', false, false, false, 126) == 'html')) {
                    // line 127
                    yield '                        <td class="';
                    yield CoreExtension::callMacro($macros['_self'], 'macro_cellClass', [(isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                        throw new RuntimeError('Variable "fullWidth" does not exist.', 127, $this->source);
                    })()), $context['col'], (((craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [], 'any', true, true, false, 127) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [], 'any', false, false, false, 127) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'class', [], 'any', false, false, false, 127)) : ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', true, true, false, 127) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', false, false, false, 127) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', false, false, false, 127)) : ([]))))], 127, $context, $this->getSourceContext());
                    yield '"';
                    if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', true, true, false, 127) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 127) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 127)) : (false))) {
                        yield ' width="';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 127), 'html', null, true);
                        yield '"';
                    }
                    yield '>';
                    yield isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                        throw new RuntimeError('Variable "value" does not exist.', 127, $this->source);
                    })();
                    yield '</td>
                    ';
                } else {
                    // line 129
                    yield '                        ';
                    $context['headingId'] = (((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                        throw new RuntimeError('Variable "id" does not exist.', 129, $this->source);
                    })()).'-heading-').craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'index', [], 'any', false, false, false, 129));
                    // line 130
                    yield '                        ';
                    $context['hasErrors'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'hasErrors', [], 'any', true, true, false, 130) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'hasErrors', [], 'any', false, false, false, 130) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'hasErrors', [], 'any', false, false, false, 130)) : (false));
                    // line 131
                    yield '                        ';
                    $context['cellName'] = ((((((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                        throw new RuntimeError('Variable "name" does not exist.', 131, $this->source);
                    })()).'[').$context['rowId']).'][').$context['colId']).']');
                    // line 132
                    yield '                        ';
                    $context['isCode'] = ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'code', [], 'any', true, true, false, 132) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'code', [], 'any', false, false, false, 132) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'code', [], 'any', false, false, false, 132)) : (false)) || (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'type', [], 'any', false, false, false, 132) == 'color'));
                    // line 133
                    yield '                        <td class="';
                    yield CoreExtension::callMacro($macros['_self'], 'macro_cellClass', [(isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                        throw new RuntimeError('Variable "fullWidth" does not exist.', 133, $this->source);
                    })()), $context['col'], (((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', true, true, false, 133) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', false, false, false, 133) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'class', [], 'any', false, false, false, 133)) : ([]))], 133, $context, $this->getSourceContext());
                    yield ' ';
                    if ((isset($context['isCode']) || array_key_exists('isCode', $context) ? $context['isCode'] : (function () {
                        throw new RuntimeError('Variable "isCode" does not exist.', 133, $this->source);
                    })())) {
                        yield 'code';
                    }
                    yield ' ';
                    if ((isset($context['hasErrors']) || array_key_exists('hasErrors', $context) ? $context['hasErrors'] : (function () {
                        throw new RuntimeError('Variable "hasErrors" does not exist.', 133, $this->source);
                    })())) {
                        yield 'error';
                    }
                    yield '"';
                    if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', true, true, false, 133) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 133) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 133)) : (false))) {
                        yield ' width="';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['col'], 'width', [], 'any', false, false, false, 133), 'html', null, true);
                        yield '"';
                    }
                    yield '>
                            ';
                    // line 134
                    yield from $this->unwrap()->yieldBlock('tablecell', $context, $blocks);
                    // line 233
                    yield '                        </td>
                    ';
                }
                // line 235
                yield '                ';
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
            unset($context['_seq'], $context['colId'], $context['col'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 236
            yield '                ';
            if ((isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                throw new RuntimeError('Variable "allowReorder" does not exist.', 236, $this->source);
            })())) {
                // line 237
                yield '<td class="thin action">
                      <div class="flex flex-nowrap">
                        <a class="move icon" title="';
                // line 239
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Reorder', 'app'), 'html', null, true);
                yield '" aria-label="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Reorder', 'app'), 'html', null, true);
                yield '" type="button" role="button"></a>
';
                // line 240
                yield craft\helpers\Cp::disclosureMenu((isset($context['actionMenuItems']) || array_key_exists('actionMenuItems', $context) ? $context['actionMenuItems'] : (function () {
                    throw new RuntimeError('Variable "actionMenuItems" does not exist.', 240, $this->source);
                })()), ['buttonAttributes' => ['aria-label' =>                 // line 242
(isset($context['actionBtnLabel']) || array_key_exists('actionBtnLabel', $context) ? $context['actionBtnLabel'] : (function () {
    throw new RuntimeError('Variable "actionBtnLabel" does not exist.', 242, $this->source);
})()), 'class' => ['action-btn'], 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter('Actions', 'app'), 'data' => ['disclosure-trigger' => true]]]);
                // line 249
                yield '
                      </div>
                    </td>';
            }
            // line 253
            if ((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                throw new RuntimeError('Variable "allowDelete" does not exist.', 253, $this->source);
            })())) {
                // line 254
                yield '<td class="thin action">
                        ';
                // line 255
                yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['delete', 'icon', (((                // line 259
                    (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                        throw new RuntimeError('Variable "minRows" does not exist.', 259, $this->source);
                    })()) && ((isset($context['totalRows']) || array_key_exists('totalRows', $context) ? $context['totalRows'] : (function () {
                        throw new RuntimeError('Variable "totalRows" does not exist.', 259, $this->source);
                    })()) <= (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                        throw new RuntimeError('Variable "minRows" does not exist.', 259, $this->source);
                    })())))) ? ('disabled') : (null))]), 'type' => 'button', 'disabled' => (                // line 262
                        (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                            throw new RuntimeError('Variable "minRows" does not exist.', 262, $this->source);
                        })()) && ((isset($context['totalRows']) || array_key_exists('totalRows', $context) ? $context['totalRows'] : (function () {
                            throw new RuntimeError('Variable "totalRows" does not exist.', 262, $this->source);
                        })()) <= (isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                            throw new RuntimeError('Variable "minRows" does not exist.', 262, $this->source);
                        })()))), 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter('Delete', 'app'), 'aria' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Delete row {index}', 'app', ['index' =>                 // line 266
                        (isset($context['rowNumber']) || array_key_exists('rowNumber', $context) ? $context['rowNumber'] : (function () {
                            throw new RuntimeError('Variable "rowNumber" does not exist.', 266, $this->source);
                        })())])]]);
                // line 269
                yield '
                    </td>';
            }
            // line 272
            yield '</tr>
        ';
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
        unset($context['_seq'], $context['rowId'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 274
        yield '    </tbody>
';
        echo craft\helpers\Html::tag('table', ob_get_clean(),         // line 81
            (isset($context['tableAttributes']) || array_key_exists('tableAttributes', $context) ? $context['tableAttributes'] : (function () {
                throw new RuntimeError('Variable "tableAttributes" does not exist.', 81, $this->source);
            })()));
        // line 276
        yield '
';
        // line 277
        if ((isset($context['allowAdd']) || array_key_exists('allowAdd', $context) ? $context['allowAdd'] : (function () {
            throw new RuntimeError('Variable "allowAdd" does not exist.', 277, $this->source);
        })())) {
            // line 278
            yield '    ';
            $context['buttonText'] = (($context['addRowLabel']) ?? ($this->extensions['craft\web\twig\Extension']->translateFilter('Add a row', 'app')));
            // line 279
            yield '    <button type="button" class="btn dashed add icon" aria-label="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['buttonText']) || array_key_exists('buttonText', $context) ? $context['buttonText'] : (function () {
                throw new RuntimeError('Variable "buttonText" does not exist.', 279, $this->source);
            })()), 'html', null, true);
            yield '">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['buttonText']) || array_key_exists('buttonText', $context) ? $context['buttonText'] : (function () {
                throw new RuntimeError('Variable "buttonText" does not exist.', 279, $this->source);
            })()), 'html', null, true);
            yield '</button>
';
        }
        // line 281
        yield '
';
        // line 282
        if ((isset($context['initJs']) || array_key_exists('initJs', $context) ? $context['initJs'] : (function () {
            throw new RuntimeError('Variable "initJs" does not exist.', 282, $this->source);
        })())) {
            // line 283
            yield '    ';
            $context['jsId'] = $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getFilter('namespaceInputId')->getCallable()((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 283, $this->source);
            })())), 'js');
            // line 284
            yield '    ';
            $context['jsName'] = $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getFilter('namespaceInputName')->getCallable()((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 284, $this->source);
            })())), 'js');
            // line 285
            yield '    ';
            $context['jsCols'] = $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['cols']) || array_key_exists('cols', $context) ? $context['cols'] : (function () {
                throw new RuntimeError('Variable "cols" does not exist.', 285, $this->source);
            })()));
            // line 286
            yield '    ';
            $context['defaultValues'] ??= null;
            // line 287
            yield '    ';
            ob_start();
            // line 288
            yield '        new Craft.EditableTable("';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['jsId']) || array_key_exists('jsId', $context) ? $context['jsId'] : (function () {
                throw new RuntimeError('Variable "jsId" does not exist.', 288, $this->source);
            })()), 'html', null, true);
            yield '", "';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['jsName']) || array_key_exists('jsName', $context) ? $context['jsName'] : (function () {
                throw new RuntimeError('Variable "jsName" does not exist.', 288, $this->source);
            })()), 'html', null, true);
            yield '", ';
            yield isset($context['jsCols']) || array_key_exists('jsCols', $context) ? $context['jsCols'] : (function () {
                throw new RuntimeError('Variable "jsCols" does not exist.', 288, $this->source);
            })();
            yield ', {
            defaultValues: ';
            // line 289
            yield ((isset($context['defaultValues']) || array_key_exists('defaultValues', $context) ? $context['defaultValues'] : (function () {
                throw new RuntimeError('Variable "defaultValues" does not exist.', 289, $this->source);
            })())) ? ($this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['defaultValues']) || array_key_exists('defaultValues', $context) ? $context['defaultValues'] : (function () {
                throw new RuntimeError('Variable "defaultValues" does not exist.', 289, $this->source);
            })()))) : ('{}');
            yield ',
            allowAdd: ';
            // line 290
            yield ((isset($context['allowAdd']) || array_key_exists('allowAdd', $context) ? $context['allowAdd'] : (function () {
                throw new RuntimeError('Variable "allowAdd" does not exist.', 290, $this->source);
            })())) ? ('true') : ('false');
            yield ',
            allowDelete: ';
            // line 291
            yield ((isset($context['allowDelete']) || array_key_exists('allowDelete', $context) ? $context['allowDelete'] : (function () {
                throw new RuntimeError('Variable "allowDelete" does not exist.', 291, $this->source);
            })())) ? ('true') : ('false');
            yield ',
            allowReorder: ';
            // line 292
            yield ((isset($context['allowReorder']) || array_key_exists('allowReorder', $context) ? $context['allowReorder'] : (function () {
                throw new RuntimeError('Variable "allowReorder" does not exist.', 292, $this->source);
            })())) ? ('true') : ('false');
            yield ',
            minRows: ';
            // line 293
            (((isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                throw new RuntimeError('Variable "minRows" does not exist.', 293, $this->source);
            })())) ? (yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['minRows']) || array_key_exists('minRows', $context) ? $context['minRows'] : (function () {
                throw new RuntimeError('Variable "minRows" does not exist.', 293, $this->source);
            })()), 'html', null, true)) : (yield 'null'));
            yield ',
            maxRows: ';
            // line 294
            (((isset($context['maxRows']) || array_key_exists('maxRows', $context) ? $context['maxRows'] : (function () {
                throw new RuntimeError('Variable "maxRows" does not exist.', 294, $this->source);
            })())) ? (yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['maxRows']) || array_key_exists('maxRows', $context) ? $context['maxRows'] : (function () {
                throw new RuntimeError('Variable "maxRows" does not exist.', 294, $this->source);
            })()), 'html', null, true)) : (yield 'null'));
            yield '
        });
    ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        craft\helpers\Template::endProfile('template', '_includes/forms/editableTable.twig');
        yield from [];
    }

    // line 134
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_tablecell(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'tablecell');
        // line 135
        switch (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
            throw new RuntimeError('Variable "col" does not exist.', 135, $this->source);
        })()), 'type', [], 'any', false, false, false, 135)) {
            case 'checkbox' :
                // line 137
                yield '<div class="checkbox-wrapper">
                                            ';
                // line 138
                yield from $this->loadTemplate('_includes/forms/checkbox', '_includes/forms/editableTable.twig', 138)->unwrap()->yield(CoreExtension::toArray(['name' =>                 // line 139
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 139, $this->source);
})()), 'value' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 140
    ($context['col'] ?? null), 'value', [], 'any', true, true, false, 140) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'value', [], 'any', false, false, false, 140) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'value', [], 'any', false, false, false, 140)) : (1)), 'checked' => ! Twig\Extension\CoreExtension::testEmpty(                // line 141
        (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
            throw new RuntimeError('Variable "value" does not exist.', 141, $this->source);
        })())), 'disabled' =>                 // line 142
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 142, $this->source);
})()), 'labelledBy' =>                 // line 143
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 143, $this->source);
})()), 'describedBy' =>                 // line 144
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 144, $this->source);
})())]));
                // line 146
                yield '                                        </div>';
                break;
            case 'color' :
                // line 148
                yield from $this->loadTemplate('_includes/forms/color', '_includes/forms/editableTable.twig', 148)->unwrap()->yield(CoreExtension::toArray(['name' =>                 // line 149
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 149, $this->source);
})()), 'value' =>                 // line 150
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 150, $this->source);
})()), 'small' => true, 'disabled' =>                 // line 152
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 152, $this->source);
})()), 'labelledBy' =>                 // line 153
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 153, $this->source);
})()), 'describedBy' =>                 // line 154
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 154, $this->source);
})())]));
                break;
            case 'date' :
                // line 157
                yield from $this->loadTemplate('_includes/forms/date', '_includes/forms/editableTable.twig', 157)->unwrap()->yield(CoreExtension::toArray(['name' =>                 // line 158
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 158, $this->source);
})()), 'value' =>                 // line 159
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 159, $this->source);
})()), 'disabled' =>                 // line 160
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 160, $this->source);
})()), 'labelledBy' =>                 // line 161
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 161, $this->source);
})()), 'describedBy' =>                 // line 162
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 162, $this->source);
})())]));
                break;
            case 'lightswitch' :
                // line 165
                yield from $this->loadTemplate('_includes/forms/lightswitch', '_includes/forms/editableTable.twig', 165)->unwrap()->yield(CoreExtension::toArray(['name' =>                 // line 166
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 166, $this->source);
})()), 'on' =>                 // line 167
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 167, $this->source);
})()), 'value' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 168
    ($context['col'] ?? null), 'value', [], 'any', true, true, false, 168) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'value', [], 'any', false, false, false, 168) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'value', [], 'any', false, false, false, 168)) : (1)), 'small' => true, 'disabled' =>                 // line 170
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 170, $this->source);
})()), 'labelledBy' =>                 // line 171
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 171, $this->source);
})()), 'describedBy' =>                 // line 172
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 172, $this->source);
})())]));
                // line 174
                yield '                                    ';
                break;
            case 'select' :
                // line 175
                yield from $this->loadTemplate('_includes/forms/select', '_includes/forms/editableTable.twig', 175)->unwrap()->yield(CoreExtension::toArray(['class' => 'small', 'name' =>                 // line 177
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 177, $this->source);
})()), 'options' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 178
    ($context['cell'] ?? null), 'options', [], 'any', true, true, false, 178) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'options', [], 'any', false, false, false, 178) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['cell'] ?? null), 'options', [], 'any', false, false, false, 178)) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
        throw new RuntimeError('Variable "col" does not exist.', 178, $this->source);
    })()), 'options', [], 'any', false, false, false, 178))), 'value' =>                 // line 179
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 179, $this->source);
})()), 'disabled' =>                 // line 180
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 180, $this->source);
})()), 'labelledBy' =>                 // line 181
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 181, $this->source);
})()), 'describedBy' =>                 // line 182
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 182, $this->source);
})())]));
                break;
            case 'time' :
                // line 185
                yield from $this->loadTemplate('_includes/forms/time', '_includes/forms/editableTable.twig', 185)->unwrap()->yield(CoreExtension::toArray(['name' =>                 // line 186
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 186, $this->source);
})()), 'value' =>                 // line 187
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 187, $this->source);
})()), 'disabled' =>                 // line 188
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 188, $this->source);
})()), 'labelledBy' =>                 // line 189
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 189, $this->source);
})()), 'describedBy' =>                 // line 190
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 190, $this->source);
})())]));
                break;
            case 'email' :
            case 'url' :
                // line 193
                yield from $this->loadTemplate('_includes/forms/text', '_includes/forms/editableTable.twig', 193)->unwrap()->yield(CoreExtension::toArray(['type' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 194
                    (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
                        throw new RuntimeError('Variable "col" does not exist.', 194, $this->source);
                    })()), 'type', [], 'any', false, false, false, 194), 'name' =>                 // line 195
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 195, $this->source);
})()), 'placeholder' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 196
    ($context['col'] ?? null), 'placeholder', [], 'any', true, true, false, 196) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'placeholder', [], 'any', false, false, false, 196) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'placeholder', [], 'any', false, false, false, 196)) : (null)), 'value' =>                 // line 197
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 197, $this->source);
})()), 'disabled' =>                 // line 198
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 198, $this->source);
})()), 'labelledBy' =>                 // line 199
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 199, $this->source);
})()), 'describedBy' =>                 // line 200
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 200, $this->source);
})())]));
                break;
            case 'autosuggest' :
            case 'template' :
                // line 203
                yield from $this->loadTemplate('_includes/forms/autosuggest', '_includes/forms/editableTable.twig', 203)->unwrap()->yield(CoreExtension::toArray(['name' =>                 // line 204
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 204, $this->source);
})()), 'suggestions' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 205
    (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
        throw new RuntimeError('Variable "col" does not exist.', 205, $this->source);
    })()), 'type', [], 'any', false, false, false, 205) == 'template')) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
        throw new RuntimeError('Variable "craft" does not exist.', 205, $this->source);
    })()), 'cp', [], 'any', false, false, false, 205), 'getTemplateSuggestions', [], 'method', false, false, false, 205)) : ([])), 'suggestEnvVars' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 206
        ($context['col'] ?? null), 'suggestEnvVars', [], 'any', true, true, false, 206) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'suggestEnvVars', [], 'any', false, false, false, 206) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'suggestEnvVars', [], 'any', false, false, false, 206)) : (false)), 'suggestAliases' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 207
            ($context['col'] ?? null), 'suggestAliases', [], 'any', true, true, false, 207) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'suggestAliases', [], 'any', false, false, false, 207) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'suggestAliases', [], 'any', false, false, false, 207)) : (false)), 'value' =>                 // line 208
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 208, $this->source);
})()), 'disabled' =>                 // line 209
(isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
    throw new RuntimeError('Variable "static" does not exist.', 209, $this->source);
})()), 'labelledBy' =>                 // line 210
(isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
    throw new RuntimeError('Variable "headingId" does not exist.', 210, $this->source);
})()), 'describedBy' =>                 // line 211
(isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
    throw new RuntimeError('Variable "describedBy" does not exist.', 211, $this->source);
})())]));
                break;
            default :
                // line 214
                if ((isset($context['static']) || array_key_exists('static', $context) ? $context['static'] : (function () {
                    throw new RuntimeError('Variable "static" does not exist.', 214, $this->source);
                })())) {
                    // line 215
                    yield '                                            <pre class="noteditable">';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                        throw new RuntimeError('Variable "value" does not exist.', 215, $this->source);
                    })()), 'html', null, true);
                    yield '</pre>
                                        ';
                } else {
                    // line 217
                    yield '                                            ';
                    if ((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                        throw new RuntimeError('Variable "value" does not exist.', 217, $this->source);
                    })())) {
                        // line 218
                        yield '                                                <div class="editable-table-preview" aria-hidden="true">';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                            throw new RuntimeError('Variable "value" does not exist.', 218, $this->source);
                        })()), 'html', null, true);
                        yield '</div>
                                            ';
                    }
                    // line 220
                    yield '                                            ';
                    yield $this->extensions['craft\web\twig\Extension']->tagFunction('textarea', ['name' =>                     // line 221
(isset($context['cellName']) || array_key_exists('cellName', $context) ? $context['cellName'] : (function () {
    throw new RuntimeError('Variable "cellName" does not exist.', 221, $this->source);
})()), 'rows' => (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 222
    ($context['col'] ?? null), 'rows', [], 'any', true, true, false, 222) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'rows', [], 'any', false, false, false, 222) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'rows', [], 'any', false, false, false, 222)) : (1)), 'placeholder' => (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 223
        ($context['col'] ?? null), 'placeholder', [], 'any', true, true, false, 223) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'placeholder', [], 'any', false, false, false, 223) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'placeholder', [], 'any', false, false, false, 223)) : (false)), 'aria' => ['labelledby' =>                     // line 225
        (isset($context['headingId']) || array_key_exists('headingId', $context) ? $context['headingId'] : (function () {
            throw new RuntimeError('Variable "headingId" does not exist.', 225, $this->source);
        })()), 'describedby' =>                     // line 226
        (isset($context['describedBy']) || array_key_exists('describedBy', $context) ? $context['describedBy'] : (function () {
            throw new RuntimeError('Variable "describedBy" does not exist.', 226, $this->source);
        })())], 'html' =>                     // line 228
(isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
    throw new RuntimeError('Variable "value" does not exist.', 228, $this->source);
})())]);
                    // line 229
                    yield '
                                        ';
                }
        }
        craft\helpers\Template::endProfile('block', 'tablecell');
        yield from [];
    }

    // line 37
    public function macro_cellClass($__fullWidth__ = null, $__col__ = null, $__class__ = null, ...$__varargs__)
    {
        $context = [
            'fullWidth' => $__fullWidth__,
            'col' => $__col__,
            'class' => $__class__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'cellClass');
            // line 38
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->mergeFilter(((is_iterable((isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
                throw new RuntimeError('Variable "class" does not exist.', 38, $this->source);
            })()))) ? ((isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
                throw new RuntimeError('Variable "class" does not exist.', 38, $this->source);
            })())) : ([(isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
                throw new RuntimeError('Variable "class" does not exist.', 38, $this->source);
            })())])), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [(craft\helpers\Template::attribute($this->env, $this->source,             // line 39
                (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
                    throw new RuntimeError('Variable "col" does not exist.', 39, $this->source);
                })()), 'type', [], 'any', false, false, false, 39).'-cell'), ((CoreExtension::inFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 40
                    (isset($context['col']) || array_key_exists('col', $context) ? $context['col'] : (function () {
                        throw new RuntimeError('Variable "col" does not exist.', 40, $this->source);
                    })()), 'type', [], 'any', false, false, false, 40), ['autosuggest', 'color', 'date', 'email', 'multiline', 'number', 'singleline', 'template', 'time', 'url'])) ? ('textual') : (null)), (((            // line 52
                        (isset($context['fullWidth']) || array_key_exists('fullWidth', $context) ? $context['fullWidth'] : (function () {
                            throw new RuntimeError('Variable "fullWidth" does not exist.', 52, $this->source);
                        })()) && (((craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'thin', [], 'any', true, true, false, 52) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'thin', [], 'any', false, false, false, 52) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['col'] ?? null), 'thin', [], 'any', false, false, false, 52)) : (false)))) ? ('thin') : (null)), ((craft\helpers\Template::attribute($this->env, $this->source,             // line 53
                            ($context['col'] ?? null), 'info', [], 'any', true, true, false, 53)) ? ('has-info') : (null))])), ' '), 'html', null, true);
            craft\helpers\Template::endProfile('macro', 'cellClass');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/editableTable.twig';
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
        return [694 => 53,  693 => 52,  692 => 40,  691 => 39,  690 => 38,  675 => 37,  665 => 229,  663 => 228,  662 => 226,  661 => 225,  660 => 223,  659 => 222,  658 => 221,  656 => 220,  650 => 218,  647 => 217,  641 => 215,  639 => 214,  633 => 211,  632 => 210,  631 => 209,  630 => 208,  629 => 207,  628 => 206,  627 => 205,  626 => 204,  625 => 203,  618 => 200,  617 => 199,  616 => 198,  615 => 197,  614 => 196,  613 => 195,  612 => 194,  611 => 193,  604 => 190,  603 => 189,  602 => 188,  601 => 187,  600 => 186,  599 => 185,  593 => 182,  592 => 181,  591 => 180,  590 => 179,  589 => 178,  588 => 177,  587 => 175,  581 => 174,  579 => 172,  578 => 171,  577 => 170,  576 => 168,  575 => 167,  574 => 166,  573 => 165,  567 => 162,  566 => 161,  565 => 160,  564 => 159,  563 => 158,  562 => 157,  556 => 154,  555 => 153,  554 => 152,  553 => 150,  552 => 149,  551 => 148,  545 => 146,  543 => 144,  542 => 143,  541 => 142,  540 => 141,  539 => 140,  538 => 139,  537 => 138,  534 => 137,  530 => 135,  522 => 134,  511 => 294,  507 => 293,  503 => 292,  499 => 291,  495 => 290,  491 => 289,  482 => 288,  479 => 287,  476 => 286,  473 => 285,  470 => 284,  467 => 283,  465 => 282,  462 => 281,  454 => 279,  451 => 278,  449 => 277,  446 => 276,  444 => 81,  441 => 274,  426 => 272,  422 => 269,  420 => 266,  419 => 262,  418 => 259,  417 => 255,  414 => 254,  412 => 253,  407 => 249,  405 => 242,  404 => 240,  398 => 239,  394 => 237,  391 => 236,  377 => 235,  373 => 233,  371 => 134,  352 => 133,  349 => 132,  346 => 131,  343 => 130,  340 => 129,  326 => 127,  324 => 126,  311 => 125,  308 => 124,  305 => 123,  302 => 122,  285 => 121,  280 => 120,  277 => 119,  274 => 118,  271 => 117,  254 => 116,  251 => 115,  246 => 112,  238 => 110,  235 => 109,  220 => 107,  215 => 105,  213 => 104,  210 => 102,  207 => 100,  205 => 99,  203 => 98,  201 => 97,  195 => 96,  192 => 95,  175 => 94,  171 => 92,  168 => 91,  165 => 90,  160 => 89,  155 => 88,  151 => 86,  148 => 85,  141 => 83,  136 => 82,  134 => 81,  130 => 79,  121 => 76,  115 => 74,  111 => 72,  107 => 71,  104 => 70,  101 => 68,  99 => 67,  97 => 63,  96 => 62,  95 => 61,  94 => 58,  93 => 57,  90 => 56,  87 => 36,  81 => 34,  79 => 33,  76 => 32,  74 => 16,  71 => 15,  69 => 14,  67 => 13,  65 => 12,  63 => 11,  61 => 10,  59 => 8,  57 => 7,  55 => 6,  53 => 5,  51 => 4,  49 => 3,  47 => 2,  45 => 1];
    }

    public function getSourceContext(): Source
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

{% set actionMenuItems = [
  {
    icon: 'arrow-up',
    label: 'Move up'|t('app'),
    attributes: {
      data: {action: 'moveUp'},
    },
  },
  {
    icon: 'arrow-down',
    label: 'Move down'|t('app'),
    attributes: {
      data: {action: 'moveDown'},
    },
  },
] %}

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
            {% set rowName = 'Row {index}'|t('app', {index: rowNumber}) %}
            {% set actionBtnLabel = \"#{rowName} #{'Actions'|t('app')}\" %}
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
                    <td class=\"thin action\">
                      <div class=\"flex flex-nowrap\">
                        <a class=\"move icon\" title=\"{{ 'Reorder'|t('app') }}\" aria-label=\"{{ 'Reorder'|t('app') }}\" type=\"button\" role=\"button\"></a>
                        {{~ disclosureMenu(actionMenuItems, {
                          buttonAttributes: {
                            'aria-label': actionBtnLabel,
                            class: ['action-btn'],
                            title: 'Actions'|t('app'),
                            data: {
                              'disclosure-trigger': true,
                            },
                          },
                        }) }}
                      </div>
                    </td>
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
", '_includes/forms/editableTable.twig', '/tmp/packages/craft5/src/templates/_includes/forms/editableTable.twig');
    }
}
