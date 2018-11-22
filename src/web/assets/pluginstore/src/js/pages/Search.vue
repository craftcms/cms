<template>
    <div>
        <h1>Showing results for “{{searchQuery}}”</h1>
        <plugin-grid :plugins="pluginsToRender" :columns="4"></plugin-grid>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import filter from 'lodash/filter'
    import includes from 'lodash/includes'
    import PluginGrid from '../components/PluginGrid'

    export default {

        components: {
            PluginGrid,
        },

        computed: {

            ...mapState({
                plugins: state => state.pluginStore.plugins,
                searchQuery: state => state.app.searchQuery,
            }),

            pluginsToRender() {
                let searchQuery = this.searchQuery

                if (!searchQuery) {
                    this.$emit('hideResults')
                    return []
                }

                this.$emit('showResults')

                return filter(this.plugins, o => {
                    if (o.packageName && includes(o.packageName.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.name && includes(o.name.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.shortDescription && includes(o.shortDescription.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.description && includes(o.description.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.developerName && includes(o.developerName.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.developerUrl && includes(o.developerUrl.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.keywords.length > 0) {
                        for (let i = 0; i < o.keywords.length; i++) {
                            if (includes(o.keywords[i].toLowerCase(), searchQuery.toLowerCase())) {
                                return true
                            }
                        }
                    }
                })
            },
        },

    }
</script>