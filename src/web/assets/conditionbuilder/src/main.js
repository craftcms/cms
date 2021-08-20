/* global Craft */
/* global Garnish */

import Vue from 'vue'
import ConditionBuilder from './ConditionBuilder'

Vue.filter('t', function(message) {
    return Craft.t(message)
});

//
// const MyComp = Vue.component('custom-input', {
//     props: ['value'],
//     template: `
//       <input v-bind:value="value" v-on:input="$emit('input', $event.target.value)">
//     `
// })

Craft.VueConditionBuilder = Garnish.Base.extend({
        init: function(settings) {
            this.setSettings(settings, Craft.VueConditionBuilder.defaults);

            const props = this.settings;

            // For any rules that has type string (a path)
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
            console.log(rules);
            props.rules = rules;

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
            maxDepth: 1,
            maxRuleUsage: null,
            groupOperatorEnabled: false
        }
    });