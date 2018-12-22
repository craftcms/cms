<template>
    <div>
        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false" :plugins="pluginsToRender" :sort.sync="sort"></plugin-search>

        <plugin-grid v-if="!showingSearchResults" :plugins="pluginsToRender" :columns="columns"></plugin-grid>
    </div>
</template>

<script>
    import clone from 'lodash/clone'
    import PluginGrid from './PluginGrid'
    import PluginSearch from './PluginSearch'

    export default {

        components: {
            PluginGrid,
            PluginSearch,
        },

        props: ['plugins', 'columns'],

        data() {
            return {
                showingSearchResults: false,
                sort: {
                    attribute: 'activeInstalls',
                    direction: 'desc',
                },
            }
        },

        computed: {

            pluginsToRender() {
                if (!this.plugins) {
                    return []
                }

                let plugins = clone(this.plugins)

                let attribute = this.sort.attribute
                let direction = this.sort.direction

                function compareASC(a, b) {
                    if (a[attribute] < b[attribute]) {
                        return -1
                    }
                    if (a[attribute] > b[attribute]) {
                        return 1
                    }
                    return 0
                }

                function compareDESC(a, b) {
                    if (a[attribute] > b[attribute]) {
                        return -1
                    }
                    if (a[attribute] < b[attribute]) {
                        return 1
                    }
                    return 0
                }

                if (direction === 'desc') {
                    plugins.sort(compareDESC)
                } else {
                    plugins.sort(compareASC)
                }

                return plugins
            }

        },

    }
</script>
