<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/autosuggest */
class __TwigTemplate_9b81355a05353a498992dc8fdff0d005 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'data' => $this->block_data(...),
            'methods' => $this->block_methods(...),
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_includes/forms/autosuggest');
        // line 1
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 1, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\vue\\VueAsset'], 'method');
        // line 2
        echo '
';
        // line 3
        if ((($context['suggestEnvVars']) ?? (false))) {
            // line 4
            echo '    ';
            $context['suggestions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((($context['suggestions']) ?? ([])), craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
            })()), 'cp', []), 'getEnvSuggestions', [0 => ((            // line 5
                $context['suggestAliases']) ?? (false)), 1 => ((            // line 6
                    $context['suggestionFilter']) ?? (null))], 'method'));
        }
        // line 10
        $context['id'] ??= 'autosuggest'.twig_random($this->env);
        // line 11
        $context['containerId'] = ((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 11, $this->source);
        })()).'-container');
        // line 12
        $context['autosuggestId'] = ((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 12, $this->source);
        })()).'-autosuggest');
        // line 13
        $context['labelledBy'] ??= null;
        // line 15
        $context['class'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'text', 1 => ((((        // line 17
            $context['disabled']) ?? (false))) ? ('disabled') : (null)), 2 => ((! ((        // line 18
                $context['size']) ?? (false))) ? ('fullwidth') : (null))]));
        // line 20
        echo '
<div id="';
        // line 21
        echo twig_escape_filter($this->env, (isset($context['containerId']) || array_key_exists('containerId', $context) ? $context['containerId'] : (function () {
            throw new RuntimeError('Variable "containerId" does not exist.', 21, $this->source);
        })()), 'html', null, true);
        echo '" class="autosuggest-container">
    ';
        // line 40
        echo '
    <vue-autosuggest
        :suggestions="filteredOptions"
        :get-suggestion-value="getSuggestionValue"
        :input-props="inputProps"
        :limit="limit"
        :component-attr-id-autosuggest="id"
        @selected="onSelected"
        @focus="updateFilteredOptions"
        @blur="onBlur"
        @input="onInputChange"
        v-model="inputProps.initialValue"
    >
        <template slot-scope="{suggestion}">
            {{suggestion.item.name || suggestion.item}}
            <span v-if="suggestion.item.hint" class="light">– {{suggestion.item.hint}}</span>
        </template>
    </vue-autosuggest>
    ';
        echo '
</div>

';
        // line 43
        ob_start();
        // line 44
        echo 'new Vue({
    el: "#';
        // line 45
        echo twig_escape_filter($this->env, twig_escape_filter($this->env, $this->env->getFilter('namespaceInputId')->getCallable()((isset($context['containerId']) || array_key_exists('containerId', $context) ? $context['containerId'] : (function () {
            throw new RuntimeError('Variable "containerId" does not exist.', 45, $this->source);
        })())), 'js'), 'html', null, true);
        echo '",

    data() {
        ';
        // line 48
        $this->displayBlock('data', $context, $blocks);
        // line 73
        echo '        return data;
    },

    methods: {
        ';
        // line 77
        $this->displayBlock('methods', $context, $blocks);
        // line 155
        echo '    }
})
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        craft\helpers\Template::endProfile('template', '_includes/forms/autosuggest');
    }

    // line 48
    public function block_data($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'data');
        // line 49
        echo '        var data = ';
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter(['query' => twig_lower_filter($this->env, ((        // line 50
            $context['value']) ?? (''))), 'selected' => '', 'filteredOptions' => [], 'suggestions' => ((        // line 53
                $context['suggestions']) ?? ([])), 'id' =>         // line 54
(isset($context['autosuggestId']) || array_key_exists('autosuggestId', $context) ? $context['autosuggestId'] : (function () {
    throw new RuntimeError('Variable "autosuggestId" does not exist.', 54, $this->source);
})()), 'inputProps' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => twig_join_filter(        // line 56
    (isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
        throw new RuntimeError('Variable "class" does not exist.', 56, $this->source);
    })()), ' '), 'initialValue' => ((        // line 57
        $context['value']) ?? ('')), 'style' => ((        // line 58
            $context['style']) ?? ('')), 'id' => $this->env->getFilter('namespaceInputId')->getCallable()(        // line 59
                (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                    throw new RuntimeError('Variable "id" does not exist.', 59, $this->source);
                })())), 'name' => $this->env->getFilter('namespaceInputName')->getCallable()(((        // line 60
                    $context['name']) ?? (''))), 'size' => ((        // line 61
                        $context['size']) ?? ('')), 'maxlength' => ((        // line 62
                            $context['maxlength']) ?? ('')), 'autofocus' => ((((        // line 63
                                $context['autofocus']) ?? (false)) && craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
                                    throw new RuntimeError('Variable "currentUser" does not exist.', 63, $this->source);
                                })()), 'getAutofocusPreferred', [], 'method')) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                    throw new RuntimeError('Variable "craft" does not exist.', 63, $this->source);
                                })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method')), 'disabled' => ((        // line 64
                                    $context['disabled']) ?? (false)), 'title' => ((        // line 65
                                        $context['title']) ?? ('')), 'placeholder' => ((        // line 66
                                            $context['placeholder']) ?? ('')), 'aria-describedby' => ((        // line 67
                                                $context['describedBy']) ?? (false)), 'aria-labelledby' => ((        // line 68
                                                    $context['labelledBy']) ?? (false))], ((        // line 69
                                                        $context['inputProps']) ?? ((($context['inputAttributes']) ?? ([])))), true)), 'limit' => ((        // line 70
                                                            $context['limit']) ?? (5)), ]);
        // line 71
        echo ';
        ';
        craft\helpers\Template::endProfile('block', 'data');
    }

    // line 77
    public function block_methods($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'methods');
        // line 78
        echo "        onInputChange(q) {
            this.query = (q || '').toLowerCase();
            this.updateFilteredOptions();
        },
        updateFilteredOptions() {
            if (this.query === '') {
                this.filteredOptions = this.suggestions;
                return;
            }

            var filtered = [];
            var i, j, sectionFilter, item, name;
            var that = this;

            for (i = 0; i < this.suggestions.length; i++) {
                sectionFilter = [];
                for (j = 0; j < this.suggestions[i].data.length; j++) {
                    item = this.suggestions[i].data[j];
                    if (
                        (item.name || item).toLowerCase().indexOf(this.query) !== -1 ||
                        (item.hint && item.hint.toLowerCase().indexOf(this.query) !== -1)
                    ) {
                        sectionFilter.push(item.name ? item : {name: item});
                    }
                }
                if (sectionFilter.length) {
                    sectionFilter.sort(function(a, b) {
                        var scoreA = that.scoreItem(a, this.query);
                        var scoreB = that.scoreItem(b, this.query);
                        if (scoreA === scoreB) {
                            return 0;
                        }
                        return scoreA < scoreB ? 1 : -1;
                    });
                    filtered.push({
                        label: this.suggestions[i].label || null,
                        data: sectionFilter.slice(0, this.limit)
                    });
                }
            }

            this.filteredOptions = filtered;
        },
        scoreItem(item) {
            var score = 0;
            if (item.name.toLowerCase().indexOf(this.query) !== -1) {
                score += 100 + this.query.length / item.name.length;
            }
            if (item.hint && item.hint.toLowerCase().indexOf(this.query) !== -1) {
                score += this.query.length / item.hint.length;
            }
            return score;
        },
        onSelected(option) {
            if (!option) {
                return;
            }

            this.selected = option.item;

            // Bring focus back to the input if they selected an alias
            if (option.item.name[0] == '@') {
                var input = this.\$el.querySelector('input');
                input.focus();
                input.selectionStart = input.selectionEnd = input.value.length;
            }
        },
        getSuggestionValue(suggestion) {
            return suggestion.item.name || suggestion.item;
        },
        onBlur(e) {
            // Clear out the autosuggestions if the focus has shifted to a new element
            if (e.relatedTarget) {
                this.filteredOptions = [];
            }
        },
        ";
        craft\helpers\Template::endProfile('block', 'methods');
    }

    public function getTemplateName()
    {
        return '_includes/forms/autosuggest';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [162 => 78,  157 => 77,  151 => 71,  149 => 70,  148 => 69,  147 => 68,  146 => 67,  145 => 66,  144 => 65,  143 => 64,  142 => 63,  141 => 62,  140 => 61,  139 => 60,  138 => 59,  137 => 58,  136 => 57,  135 => 56,  134 => 54,  133 => 53,  132 => 50,  130 => 49,  125 => 48,  117 => 155,  115 => 77,  109 => 73,  107 => 48,  101 => 45,  98 => 44,  96 => 43,  72 => 40,  68 => 21,  65 => 20,  63 => 18,  62 => 17,  61 => 15,  59 => 13,  57 => 12,  55 => 11,  53 => 10,  50 => 6,  49 => 5,  47 => 4,  45 => 3,  42 => 2,  40 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% do view.registerAssetBundle(\"craft\\\\web\\\\assets\\\\vue\\\\VueAsset\") %}

{% if suggestEnvVars ?? false %}
    {% set suggestions = (suggestions ?? [])|merge(craft.cp.getEnvSuggestions(
        suggestAliases ?? false,
        suggestionFilter ?? null
    )) %}
{% endif %}

{%- set id = id ?? \"autosuggest#{random()}\" %}
{%- set containerId = \"#{id}-container\" %}
{%- set autosuggestId = \"#{id}-autosuggest\" %}
{%- set labelledBy = labelledBy ?? null -%}

{%- set class = (class ?? [])|explodeClass|merge([
    'text',
    (disabled ?? false) ? 'disabled' : null,
    not (size ?? false) ? 'fullwidth' : null,
]|filter) %}

<div id=\"{{ containerId }}\" class=\"autosuggest-container\">
    {% verbatim %}
    <vue-autosuggest
        :suggestions=\"filteredOptions\"
        :get-suggestion-value=\"getSuggestionValue\"
        :input-props=\"inputProps\"
        :limit=\"limit\"
        :component-attr-id-autosuggest=\"id\"
        @selected=\"onSelected\"
        @focus=\"updateFilteredOptions\"
        @blur=\"onBlur\"
        @input=\"onInputChange\"
        v-model=\"inputProps.initialValue\"
    >
        <template slot-scope=\"{suggestion}\">
            {{suggestion.item.name || suggestion.item}}
            <span v-if=\"suggestion.item.hint\" class=\"light\">– {{suggestion.item.hint}}</span>
        </template>
    </vue-autosuggest>
    {% endverbatim %}
</div>

{% js %}
new Vue({
    el: \"#{{ containerId|namespaceInputId|e('js') }}\",

    data() {
        {% block data %}
        var data = {{ {
            query: (value ?? '')|lower,
            selected: '',
            filteredOptions: [],
            suggestions: suggestions ?? [],
            id: autosuggestId,
            inputProps: {
                class: class|join(' '),
                initialValue: value ?? '',
                style: style ?? '',
                id: id|namespaceInputId,
                name: (name ?? '')|namespaceInputName,
                size: size ?? '',
                maxlength: maxlength ?? '',
                autofocus: (autofocus ?? false) and currentUser.getAutofocusPreferred() and not craft.app.request.isMobileBrowser(true),
                disabled: disabled ?? false,
                title: title ?? '',
                placeholder: placeholder ?? '',
                'aria-describedby': describedBy ?? false,
                'aria-labelledby': labelledBy ?? false,
            }|merge(inputProps ?? inputAttributes ?? [], recursive=true)|filter,
            limit: limit ?? 5
        }|json_encode|raw }};
        {% endblock %}
        return data;
    },

    methods: {
        {% block methods %}
        onInputChange(q) {
            this.query = (q || '').toLowerCase();
            this.updateFilteredOptions();
        },
        updateFilteredOptions() {
            if (this.query === '') {
                this.filteredOptions = this.suggestions;
                return;
            }

            var filtered = [];
            var i, j, sectionFilter, item, name;
            var that = this;

            for (i = 0; i < this.suggestions.length; i++) {
                sectionFilter = [];
                for (j = 0; j < this.suggestions[i].data.length; j++) {
                    item = this.suggestions[i].data[j];
                    if (
                        (item.name || item).toLowerCase().indexOf(this.query) !== -1 ||
                        (item.hint && item.hint.toLowerCase().indexOf(this.query) !== -1)
                    ) {
                        sectionFilter.push(item.name ? item : {name: item});
                    }
                }
                if (sectionFilter.length) {
                    sectionFilter.sort(function(a, b) {
                        var scoreA = that.scoreItem(a, this.query);
                        var scoreB = that.scoreItem(b, this.query);
                        if (scoreA === scoreB) {
                            return 0;
                        }
                        return scoreA < scoreB ? 1 : -1;
                    });
                    filtered.push({
                        label: this.suggestions[i].label || null,
                        data: sectionFilter.slice(0, this.limit)
                    });
                }
            }

            this.filteredOptions = filtered;
        },
        scoreItem(item) {
            var score = 0;
            if (item.name.toLowerCase().indexOf(this.query) !== -1) {
                score += 100 + this.query.length / item.name.length;
            }
            if (item.hint && item.hint.toLowerCase().indexOf(this.query) !== -1) {
                score += this.query.length / item.hint.length;
            }
            return score;
        },
        onSelected(option) {
            if (!option) {
                return;
            }

            this.selected = option.item;

            // Bring focus back to the input if they selected an alias
            if (option.item.name[0] == '@') {
                var input = this.\$el.querySelector('input');
                input.focus();
                input.selectionStart = input.selectionEnd = input.value.length;
            }
        },
        getSuggestionValue(suggestion) {
            return suggestion.item.name || suggestion.item;
        },
        onBlur(e) {
            // Clear out the autosuggestions if the focus has shifted to a new element
            if (e.relatedTarget) {
                this.filteredOptions = [];
            }
        },
        {% endblock %}
    }
})
{% endjs %}
", '_includes/forms/autosuggest', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/autosuggest.twig');
    }
}
