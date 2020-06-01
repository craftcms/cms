/* global Craft */
/* global Garnish */

import Vue from 'vue'
import ConditionBuilder from './ConditionBuilder'

Vue.filter('t', function(message) {
    return Craft.t(message)
});

const MyComp = Vue.component('custom-input', {
    props: ['value'],
    template: `
      <input
              v-bind:value="value"
              v-on:input="$emit('input', $event.target.value)"
      >
    `
})

Craft.VueConditionBuilder = Garnish.Base.extend({
        init: function(settings) {
            this.setSettings(settings, Craft.VueConditionBuilder.defaults);

            if (this.settings.rules.length == 0) {
                this.settings.rules = [
                    {
                        type: "custom-component",
                        id: "slider",
                        label: "Slider",
                        operators: [],
                        component: MyComp
                    },
                    {
                        type: "custom-component",
                        id: "my-condition",
                        label: "My Condiditon",
                        operators: [],
                        component: './component/Slider'
                    },
                    {
                        type: "text",
                        id: "vegetable",
                        label: "Vegetable",
                    },
                    {
                        type: "text",
                        id: "another",
                        label: "Another One",
                        default: '1,2,3'
                    },
                    {
                        type: "radio",
                        id: "fruit",
                        label: "Fruit",
                        choices: [
                            {label: "Apple", value: "apple"},
                            {label: "Banana", value: "banana"}
                        ]
                    },
                ];
            }
            this.settings.query = {
                "logicalOperator": "all",
                "children": [
                    {
                        "type": "condition-builder-rule",
                        "query": {
                            "rule": "slider",
                            "operand": "Slider",
                            "value": null
                        }
                    },
                    {
                        "type": "condition-builder-rule",
                        "query": {
                            "rule": "vegetable",
                            "operator": "equals",
                            "operand": "Vegetable",
                            "value": "esafewqrew"
                        }
                    },
                    {
                        "type": "condition-builder-rule",
                        "query": {
                            "rule": "my-condition",
                            "operand": "My Condiditon",
                            "value": "rewqrew"
                        }
                    },
                    {
                        "type": "condition-builder-rule",
                        "query": {
                            "rule": "fruit",
                            "operand": "Fruit",
                            "value": "banana"
                        }
                    },
                    {
                        "type": "condition-builder-rule",
                        "query": {
                            "rule": "another",
                            "operator": "does not contain",
                            "operand": "Another One",
                            "value": "rewqrewqrew"
                        }
                    }
                ]
            }

            const props = this.settings;

            let rules = props.rules.map((rule) => {
                const type = (typeof rule.component);
                if (type == "string") {
                    rule['component'] = Vue.component(
                        rule.id,
                        // A dynamic import returns a Promise.
                        () => import(rule.component)
                    )
                }
                return rule
            })
            props.rules = rules;
            props.value = props.query;

            return new Vue({
                components: {
                    ConditionBuilder
                },
                data() {
                    return {};
                },
                render: (h) => {
                    return h(ConditionBuilder, {
                        props: props
                    })
                }
            }).$mount(this.settings.container);
        },
    },
    {
        defaults: {
            rules: [],
            query: {},
            container: null,
            maxDepth: 1
        }
    });