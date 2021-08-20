<template>
  <div class="vue-condition-builder">
    <slot v-bind="conditionBuilderProps">
      <condition-builder-group v-bind="conditionBuilderProps" :query.sync="query"/>
    </slot>
  </div>
</template>

<script>
    /* eslint-disable vue/require-default-prop */
    import ConditionBuilderGroup from './layouts/cp/CraftCpGroup';
    import deepClone from './utilities.js';

    var defaultLabels = {
        matchTypes: [
            {"id": "all", "label": "All"},
            {"id": "any", "label": "Any"},
        ],
        addRule: "Add Rule",
        removeRule: "Delete Rule",
        addGroup: "Add Group",
        removeGroup: "Delete Group",
        textInputPlaceholder: "Value",
    };

    export default {
        name: 'ConditionBuilder',

        components: {
            ConditionBuilderGroup
        },

        props: {
            rules: Array,
            labels: {
                type: Object,
                default() {
                    return defaultLabels;
                }
            },
            maxDepth: {
                type: Number,
                default: 1,
                validator: function(value) {
                    return value >= 1
                }
            },
            groupOperatorEnabled: {
                type: Boolean,
                default: true
            },
            value: Object
        },

        data() {
            return {
                query: {
                    logicalOperator: this.labels.matchTypes[0].id,
                    children: []
                },
                ruleTypes: {
                    "text": {
                        operators: ['equals', 'does not equal', 'contains', 'does not contain', 'is empty', 'is not empty', 'begins with', 'ends with'],
                        inputType: "text",
                        id: "text-field"
                    },
                    "numeric": {
                        operators: ['=', '<>', '<', '<=', '>', '>='],
                        inputType: "number",
                        id: "number-field"
                    },
                    "custom": {
                        operators: [],
                        inputType: "text",
                        id: "custom-field"
                    },
                    "radio": {
                        operators: [],
                        choices: [],
                        inputType: "radio",
                        id: "radio-field"
                    },
                    "checkbox": {
                        operators: [],
                        choices: [],
                        inputType: "checkbox",
                        id: "checkbox-field"
                    },
                    "select": {
                        operators: [],
                        choices: [],
                        inputType: "select",
                        id: "select-field"
                    },
                    "multi-select": {
                        operators: ['='],
                        choices: [],
                        inputType: "select",
                        id: "multi-select-field"
                    },
                }
            }
        },

        computed: {
            mergedLabels() {
                return Object.assign({}, defaultLabels, this.labels);
            },

            mergedRules() {
                var mergedRules = [];
                var vm = this;

                vm.rules.forEach(function(rule) {
                    // If we know this rule, set the defaults rule data
                    if (typeof vm.ruleTypes[rule.type] !== "undefined") {
                        mergedRules.push(Object.assign({}, vm.ruleTypes[rule.type], rule));
                    } else {
                        mergedRules.push(rule);
                    }
                });

                return mergedRules;
            },
            mergedAvailableRules() {
                var availableRules = this.mergedRules;

                // var ruleCountById = [];
                // this.query.forEach(function(rule) {
                //
                // });
                // count all used rules from the query
                // look up the maxRuleUsage
                // only return rules that have not used up the maxRuleUsage

                return availableRules;
            },
            conditionBuilderProps() {
                return {
                    index: 0,
                    depth: 1,
                    maxDepth: this.maxDepth,
                    ruleTypes: this.ruleTypes,
                    rules: this.mergedRules,
                    labels: this.mergedLabels,
                    availableRules: this.mergedAvailableRules,
                    groupOperatorEnabled: this.groupOperatorEnabled
                }
            }
        },

        mounted() {
            this.$watch(
                'query',
                newQuery => {
                    console.log(JSON.stringify(newQuery, null, 2));
                    if (JSON.stringify(newQuery) !== JSON.stringify(this.value)) {
                        this.$emit('input', deepClone(newQuery))
                    }
                }, {
                    deep: true
                });

            this.$watch(
                'value',
                newValue => {
                    if (JSON.stringify(newValue) !== JSON.stringify(this.query)) {
                        this.query = deepClone(newValue);
                    }
                }, {
                    deep: true
                });

            if (typeof this.$options.propsData.value !== "undefined") {
                this.query = Object.assign(this.query, this.$options.propsData.value);
            }
        }
    }
</script>

<style lang="scss">
  @import 'styles/conditionbuilder.scss';
  .vue-condition-builder{
    margin-right:-24px;
  }
</style>
