<template>
    <div v-if="featuredPlugin">
        <h1>{{featuredPlugin.title}}</h1>
        <plugin-grid :columns="4" :plugins="getPluginsByIds(featuredPlugin.plugins)"></plugin-grid>
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'
    import PluginGrid from '../components/PluginGrid'

    export default {

        components: {
            PluginGrid,
        },

        computed: {

            ...mapGetters({
                getFeaturedPlugin: 'pluginStore/getFeaturedPlugin',
                getPluginsByIds: 'pluginStore/getPluginsByIds',
            }),

            featuredPlugin() {
                return this.getFeaturedPlugin(this.$route.params.id)
            }

        },

    }
</script>