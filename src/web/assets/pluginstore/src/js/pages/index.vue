<template>
    <div class="ps-container">
        <template v-if="featuredPlugins">
            <div v-for="(featuredPlugin, key) in featuredPlugins" :key="key">
                <div class="flex items-baseline justify-between" :class="{'mt-8': key > 0}">
                    <h2>{{ featuredPlugin.title }}</h2>
                    <router-link class="right" :to="'/featured/'+featuredPlugin.id">{{ "See all"|t('app') }}</router-link>
                </div>
                <plugin-grid :plugins="getPluginsByIds(featuredPlugin.plugins.slice(0, featuredPlugin.limit))"></plugin-grid>
            </div>
        </template>

        <template v-if="activeTrialPlugins.length > 0">
            <h2>{{ "Active Trials"|t('app') }}</h2>
            <plugin-grid :plugins="activeTrialPlugins" :trialMode="true"></plugin-grid>
        </template>
    </div>
</template>


<script>
    import {mapState, mapGetters} from 'vuex'
    import PluginGrid from '../components/PluginGrid'

    export default {

        components: {
            PluginGrid,
        },

        computed: {

            ...mapState({
                featuredPlugins: state => state.pluginStore.featuredPlugins,
            }),

            ...mapGetters({
                activeTrialPlugins: 'cart/activeTrialPlugins',
                getPluginsByIds: 'pluginStore/getPluginsByIds',
            }),

        },

        mounted() {
            // show a plugin?
            const pluginHandle = this.$route.params.pluginHandle
            if (pluginHandle) {
                this.$router.replace({path: '/'})
                const plugin = this.$store.getters['pluginStore/getPluginByHandle'](pluginHandle)

                if (this.$root.pluginStoreDataLoaded) {
                    // show plugin
                    this.$root.showPlugin(plugin)
                } else {
                    // wait for the cart to be ready
                    this.$root.$on('allDataLoaded', function() {
                        // show plugin
                        this.$root.showPlugin(plugin)
                    }.bind(this))
                }
            }
        }

    }
</script>
