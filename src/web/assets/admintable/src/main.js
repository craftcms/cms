/* global Craft */
/* global Garnish */

import Vue from 'vue'
import App from './App'
import {t} from '../../pluginstore/src/js/filters/craft'

Vue.filter('t', t)

Craft.VueAdminTable = Garnish.Base.extend({
    init: function(settings) {
        this.setSettings(settings, Craft.VueAdminTable.defaults);

        const props = this.settings;

        return new Vue({
            components: {
                App
            },
            data() {
                return {};
            },
            render: (h) => {
                return h(App, {
                    props: props
                })
            },
        }).$mount(this.settings.container);
    },
},
{
    defaults: {
        actions: [],
        checkboxes: false,
        columns: [],
        container: null,
        deleteAction: null,
        reorderAction: null,
        reorderSuccessMessage: Craft.t('app', 'Items reordered.') ,
        reorderFailMessage:    Craft.t('app', 'Couldn’t reorder items.'),
        search: false,
        searchPlaceholder: Craft.t('app', 'Search'),
        tableData: [],
        tableDataEndpoint: null,
    }
});